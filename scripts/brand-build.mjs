// Builds every logo variant from the master PNG (docs/design/logo-source.png).
//
//   npm run brand:build
//
// 1. Clean-up: pixels with alpha < 40 become fully transparent (the master has
//    ~67,000 near-invisible specks that smudge on dark backgrounds). Nothing else
//    is changed.
// 2. Crops are found from the artwork itself (connected shapes), not hard-coded:
//    the tagline is the row of small shapes under the wordmark, and the mark is
//    the left-most shape (the "A" with the chicken and leaf), cut out along its
//    own outline so no part of the "v" comes with it.
// 3. Writes public/brand/* (PNG + WebP, 1x and 2x), icons, favicon.ico, the
//    e-mail logo, og-image, preview images in docs/design/screens/, and
//    config/brand.php with the pixel size of each variant.
//
// Generated files are committed; the server never runs this.

import { createHash } from 'node:crypto';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import sharp from 'sharp';

const SOURCE = 'docs/design/logo-source.png';
const OUT = 'public/brand';
const SCREENS = 'docs/design/screens';
const ALPHA_MIN = 40;
const DARK_GREEN = '#034C33';

mkdirSync(OUT, { recursive: true });
mkdirSync(SCREENS, { recursive: true });

const sourceBytes = readFileSync(SOURCE);
// The version changes when the master OR this build changes, so browsers fetch fixed files again.
const version = createHash('sha1').update(sourceBytes).update(readFileSync(new URL(import.meta.url))).digest('hex').slice(0, 10);
const { data, info } = await sharp(sourceBytes).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
const W = info.width;
const H = info.height;

// 1. Clean-up.
let cleared = 0;
for (let i = 0; i < data.length; i += 4) {
    if (data[i + 3] > 0 && data[i + 3] < ALPHA_MIN) {
        data[i] = data[i + 1] = data[i + 2] = data[i + 3] = 0;
        cleared++;
    } else if (data[i + 3] === 0) {
        data[i] = data[i + 1] = data[i + 2] = 0;
    }
}

const raw = (buffer, width, height) => sharp(buffer, { raw: { width, height, channels: 4 } });
await raw(data, W, H).png({ compressionLevel: 9 }).toFile(`${OUT}/logo-clean.png`);

// 2. Connected shapes (8-connected, on the cleaned alpha).
const label = new Int32Array(W * H).fill(-1);
const shapes = [];
for (let start = 0; start < W * H; start++) {
    if (label[start] !== -1 || data[start * 4 + 3] === 0) continue;
    const shape = { id: shapes.length, pixels: 0, x0: W, y0: H, x1: 0, y1: 0 };
    const stack = [start];
    label[start] = shape.id;
    while (stack.length) {
        const p = stack.pop();
        const x = p % W;
        const y = (p - x) / W;
        shape.pixels++;
        shape.x0 = Math.min(shape.x0, x); shape.x1 = Math.max(shape.x1, x);
        shape.y0 = Math.min(shape.y0, y); shape.y1 = Math.max(shape.y1, y);
        for (let dy = -1; dy <= 1; dy++) {
            for (let dx = -1; dx <= 1; dx++) {
                const nx = x + dx, ny = y + dy;
                if (nx < 0 || ny < 0 || nx >= W || ny >= H) continue;
                const q = ny * W + nx;
                if (label[q] === -1 && data[q * 4 + 3] > 0) { label[q] = shape.id; stack.push(q); }
            }
        }
    }
    shapes.push(shape);
}

const tallest = Math.max(...shapes.map((s) => s.y1 - s.y0));
const wordmark = shapes.filter((s) => s.y1 - s.y0 > tallest * 0.4);
const wordmarkBottom = Math.max(...wordmark.map((s) => s.y1));
const tagline = shapes.filter((s) => s.y0 > wordmarkBottom);
const mark = wordmark.reduce((a, b) => (b.x0 < a.x0 ? b : a));

if (tagline.length === 0 || wordmark.length < 3) {
    throw new Error('Could not find the wordmark and tagline rows; check the master logo.');
}

const box = (list) => ({
    x0: Math.min(...list.map((s) => s.x0)), y0: Math.min(...list.map((s) => s.y0)),
    x1: Math.max(...list.map((s) => s.x1)), y1: Math.max(...list.map((s) => s.y1)),
});

// A plain rectangular crop of the cleaned master (plus padding): every pixel in the
// box is kept, so nothing in the artwork can be lost (the i-dot was, once).
function crop(b, pad = 2) {
    const x = Math.max(0, b.x0 - pad), y = Math.max(0, b.y0 - pad);
    const w = Math.min(W - 1, b.x1 + pad) - x + 1, h = Math.min(H - 1, b.y1 + pad) - y + 1;
    const out = Buffer.alloc(w * h * 4);
    for (let row = 0; row < h; row++) {
        data.copy(out, row * w * 4, ((y + row) * W + x) * 4, ((y + row) * W + x + w) * 4);
    }
    return { buffer: out, width: w, height: h, box: { x, y, width: w, height: h } };
}

// The mark only: the pixels of the given shapes, cut along their own outline, on a
// square canvas. This is the one approved exception to "crop only" (Design Brief §2.3).
function cut(list, pad = 2, square = false) {
    const b = box(list);
    const ids = new Set(list.map((s) => s.id));
    let w = b.x1 - b.x0 + 1 + pad * 2;
    let h = b.y1 - b.y0 + 1 + pad * 2;
    let ox = pad, oy = pad;
    if (square) {
        const side = Math.max(w, h);
        ox += Math.floor((side - w) / 2); oy += Math.floor((side - h) / 2);
        w = h = side;
    }
    const out = Buffer.alloc(w * h * 4);
    for (let y = b.y0; y <= b.y1; y++) {
        for (let x = b.x0; x <= b.x1; x++) {
            const p = y * W + x;
            if (!ids.has(label[p])) continue;
            const t = ((y - b.y0 + oy) * w + (x - b.x0 + ox)) * 4;
            data.copy(out, t, p * 4, p * 4 + 4);
        }
    }
    return { buffer: out, width: w, height: h, box: { x: b.x0 - ox, y: b.y0 - oy, width: w, height: h }, ids };
}

// logo-compact: everything above the tagline row (the letters AND the i-dot).
const aboveTagline = shapes.filter((s) => !tagline.includes(s));
const compactBox = box(aboveTagline);
if (tagline.some((s) => s.y0 <= compactBox.y1 + 2)) {
    throw new Error('The tagline overlaps the compact crop; check the master logo.');
}

const variants = {
    'logo-full': crop(box(shapes)),
    'logo-compact': crop(compactBox),
    'logo-mark': cut([mark], Math.round((mark.x1 - mark.x0) * 0.06), true),
};

// Small shapes inside the wordmark row, such as the dot of the "i": checked below.
const details = aboveTagline.filter((s) => !wordmark.includes(s));

// Display sizes (1x); 2x files have double the pixels.
const sizes = {
    'logo-full': { width: 320 },
    'logo-compact': { height: 44 },
    'logo-mark': { width: 64 },
};

const brand = { version, source: SOURCE, cleared_pixels: cleared, variants: {} };

for (const [name, v] of Object.entries(variants)) {
    const master = await raw(v.buffer, v.width, v.height).png().toBuffer();
    const want = sizes[name];
    const width1 = want.width ?? Math.round((v.width * want.height) / v.height);
    const height1 = want.height ?? Math.round((v.height * want.width) / v.width);
    for (const [suffix, scale] of [['', 1], ['@2x', 2]]) {
        const resized = sharp(master).resize(width1 * scale, height1 * scale, { fit: 'fill', kernel: 'lanczos3' });
        await resized.clone().png({ compressionLevel: 9 }).toFile(`${OUT}/${name}${suffix}.png`);
        await resized.clone().webp({ lossless: true, effort: 6 }).toFile(`${OUT}/${name}${suffix}.webp`);
    }
    brand.variants[name] = { width: width1, height: height1 };
    variants[name].png = master;
}

// Icons from the mark.
const markPng = variants['logo-mark'].png;
const icon = (size, background = null) => {
    let img = sharp(markPng).resize(size, size, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } });
    if (background) {
        const inner = Math.round(size * 0.82);
        return sharp(markPng).resize(inner, inner, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).png().toBuffer()
            .then((b) => sharp({ create: { width: size, height: size, channels: 4, background } })
                .composite([{ input: b, left: Math.floor((size - inner) / 2), top: Math.floor((size - inner) / 2) }]).png({ compressionLevel: 9 }).toBuffer());
    }
    return img.png({ compressionLevel: 9 }).toBuffer();
};

const icons = {
    'favicon-16.png': await icon(16),
    'favicon-32.png': await icon(32),
    'apple-touch-icon.png': await icon(180, '#ffffff'),
    'icon-192.png': await icon(192),
    'icon-512.png': await icon(512),
};
for (const [file, buffer] of Object.entries(icons)) writeFileSync(`${OUT}/${file}`, buffer);

// favicon.ico with PNG-compressed 16 and 32 px images.
function ico(images) {
    const header = Buffer.alloc(6 + images.length * 16);
    header.writeUInt16LE(0, 0); header.writeUInt16LE(1, 2); header.writeUInt16LE(images.length, 4);
    let offset = header.length;
    images.forEach(({ size, png }, i) => {
        const e = 6 + i * 16;
        header.writeUInt8(size >= 256 ? 0 : size, e); header.writeUInt8(size >= 256 ? 0 : size, e + 1);
        header.writeUInt16LE(1, e + 4); header.writeUInt16LE(32, e + 6);
        header.writeUInt32LE(png.length, e + 8); header.writeUInt32LE(offset, e + 12);
        offset += png.length;
    });
    return Buffer.concat([header, ...images.map((i) => i.png)]);
}
const favicon = ico([{ size: 16, png: icons['favicon-16.png'] }, { size: 32, png: icons['favicon-32.png'] }]);
writeFileSync(`${OUT}/favicon.ico`, favicon);
writeFileSync('public/favicon.ico', favicon);

// A logo on a white rounded panel (for dark backgrounds).
async function boxed(png, height, padding, radius) {
    const logo = await sharp(png).resize({ height }).png().toBuffer();
    const { width } = await sharp(logo).metadata();
    const w = width + padding * 2 + (width % 2), h = height + padding * 2;
    const panel = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}"><rect width="${w}" height="${h}" rx="${radius}" fill="#fff"/></svg>`);
    return { buffer: await sharp(panel).composite([{ input: logo, left: padding, top: padding }]).png({ compressionLevel: 9 }).toBuffer(), width: w, height: h, placement: { left: padding, top: padding, width, height } };
}
const compactBoxed = await boxed(variants['logo-compact'].png, 88, 16, 16);
writeFileSync(`${OUT}/logo-compact-boxed@2x.png`, compactBoxed.buffer);
brand.variants['logo-compact-boxed'] = { width: compactBoxed.width / 2, height: compactBoxed.height / 2 };

// E-mail logo: compact, 400 px wide (shown at 200 px).
await sharp(variants['logo-compact'].png).resize({ width: 400 }).png({ compressionLevel: 9 }).toFile(`${OUT}/logo-email.png`);
const emailMeta = await sharp(`${OUT}/logo-email.png`).metadata();
brand.variants['logo-email'] = { width: emailMeta.width / 2, height: emailMeta.height / 2 };

// Social preview: full logo centred on white.
const og = await sharp(variants['logo-full'].png).resize({ width: 860 }).png().toBuffer();
const ogHeight = (await sharp(og).metadata()).height;
const ogPlacement = { left: Math.floor((1200 - 860) / 2), top: Math.floor((630 - ogHeight) / 2), width: 860, height: ogHeight };
await sharp({ create: { width: 1200, height: 630, channels: 4, background: '#ffffff' } })
    .composite([{ input: og, left: ogPlacement.left, top: ogPlacement.top }]).png({ compressionLevel: 9 }).toFile(`${OUT}/og-image.png`);
brand.variants['og-image'] = { width: 1200, height: 630 };

// Previews for the owner: the cleaned logo large on white and on dark green,
// and the "Nutra" area before/after on dark green.
async function onBackground(png, background, file) {
    const logo = await sharp(png).resize({ width: 1800 }).png().toBuffer();
    const { height } = await sharp(logo).metadata();
    await sharp({ create: { width: 2000, height: height + 200, channels: 4, background } })
        .composite([{ input: logo, left: 100, top: 100 }]).png().toFile(`${SCREENS}/${file}`);
}
const cleanFull = await sharp(`${OUT}/logo-clean.png`).png().toBuffer();
await onBackground(cleanFull, '#ffffff', 'logo-clean-on-white.png');
await onBackground(cleanFull, DARK_GREEN, 'logo-clean-on-green.png');
await onBackground(sourceBytes, DARK_GREEN, 'logo-original-on-green.png');

// Black shows the specks most clearly; dark green is the real footer colour.
const region = { left: 880, top: 120, width: 1100, height: 380 };
for (const [background, file] of [[DARK_GREEN, 'on-green'], ['#000000', 'on-black']]) {
    const before = await sharp(sourceBytes).extract(region).flatten({ background }).png().toBuffer();
    const after = await sharp(`${OUT}/logo-clean.png`).extract(region).flatten({ background }).png().toBuffer();
    await sharp({ create: { width: region.width, height: region.height * 2 + 20, channels: 4, background: '#ffffff' } })
        .composite([{ input: before, left: 0, top: 0 }, { input: after, left: 0, top: region.height + 20 }])
        .png().toFile(`${SCREENS}/logo-nutra-before-after-${file}.png`);
}

// ---------------------------------------------------------------------------
// Checks — the build fails if any generated file loses part of the artwork.
//
// 1. Every output must match the cleaned master within its crop area, allowing
//    only for resizing: the reference is cut straight from logo-clean.png (not
//    from the variant buffers), resized to the placed size, and compared block by
//    block after compositing both over the same background.
// 2. Every small detail of the wordmark row (the dot of the "i") must still show
//    its own colour where it belongs in every output that contains the wordmark.
// ---------------------------------------------------------------------------
const cleanPng = await sharp(`${OUT}/logo-clean.png`).png().toBuffer();
const failures = [];

async function reference(variant, placement) {
    const { x, y, width, height } = variant.box;
    let img;
    if (variant === variants['logo-mark']) {
        // The mark only: the master masked to the mark's own outline (the approved cut).
        img = raw(variant.buffer, variant.width, variant.height);
    } else {
        img = sharp(cleanPng).extract({ left: x, top: y, width, height });
    }
    return img.resize(placement.width, placement.height, { fit: 'fill', kernel: 'lanczos3' }).ensureAlpha().raw().toBuffer();
}

const over = (c, a, bg) => c * a / 255 + bg * (1 - a / 255);

async function check(file, variant, placement, { background = 128, wordmark = true } = {}) {
    const out = await sharp(`${OUT}/${file}`).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
    const ref = await reference(variant, placement);
    const block = Math.max(4, Math.round(Math.min(placement.width, placement.height) / 12));
    let total = 0, count = 0, worst = 0;

    for (let by = 0; by < placement.height; by += block) {
        for (let bx = 0; bx < placement.width; bx += block) {
            let sum = 0, n = 0;
            for (let y = by; y < Math.min(by + block, placement.height); y++) {
                for (let x = bx; x < Math.min(bx + block, placement.width); x++) {
                    const o = ((placement.top + y) * out.info.width + placement.left + x) * 4;
                    const r = (y * placement.width + x) * 4;
                    for (let c = 0; c < 3; c++) {
                        sum += Math.abs(over(out.data[o + c], out.data[o + 3], background) - over(ref[r + c], ref[r + 3], background));
                    }
                    n += 3;
                }
            }
            total += sum; count += n;
            worst = Math.max(worst, sum / n);
        }
    }

    if (total / count > 4 || worst > 30) {
        failures.push(`${file}: does not match the cleaned master (mean difference ${(total / count).toFixed(1)}, worst block ${worst.toFixed(1)}).`);
    }

    if (!wordmark) return;

    for (const detail of details) {
        // The detail's own colour in the master (median of its opaque pixels).
        const colours = [];
        for (let y = detail.y0; y <= detail.y1; y++) {
            for (let x = detail.x0; x <= detail.x1; x++) {
                const p = (y * W + x) * 4;
                if (label[y * W + x] === detail.id && data[p + 3] > 200) colours.push([data[p], data[p + 1], data[p + 2]]);
            }
        }
        const median = [0, 1, 2].map((c) => colours.map((v) => v[c]).sort((a, b) => a - b)[colours.length >> 1]);
        const cx = placement.left + ((detail.x0 + detail.x1) / 2 - variant.box.x) / variant.box.width * placement.width;
        const cy = placement.top + ((detail.y0 + detail.y1) / 2 - variant.box.y) / variant.box.height * placement.height;
        const radius = Math.max(0, Math.floor((detail.x1 - detail.x0) / variant.box.width * placement.width / 6));
        const seen = [0, 0, 0];
        let n = 0;
        for (let y = Math.round(cy) - radius; y <= Math.round(cy) + radius; y++) {
            for (let x = Math.round(cx) - radius; x <= Math.round(cx) + radius; x++) {
                const o = (y * out.info.width + x) * 4;
                for (let c = 0; c < 3; c++) seen[c] += over(out.data[o + c], out.data[o + 3], background);
                n++;
            }
        }
        const got = seen.map((v) => v / n);
        const off = Math.max(...got.map((v, c) => Math.abs(v - median[c])));
        if (off > 45) {
            failures.push(`${file}: the small shape at (${detail.x0},${detail.y0}) in the master (the i-dot) is missing or recoloured — expected rgb(${median.join(',')}), found rgb(${got.map(Math.round).join(',')}).`);
        }
    }
}

for (const name of ['logo-full', 'logo-compact', 'logo-mark']) {
    const { width, height } = brand.variants[name];
    for (const [suffix, scale] of [['', 1], ['@2x', 2]]) {
        for (const ext of ['png', 'webp']) {
            await check(`${name}${suffix}.${ext}`, variants[name], { left: 0, top: 0, width: width * scale, height: height * scale }, { wordmark: name !== 'logo-mark' });
        }
    }
}
for (const [file, size] of [['favicon-16.png', 16], ['favicon-32.png', 32], ['icon-192.png', 192], ['icon-512.png', 512]]) {
    await check(file, variants['logo-mark'], { left: 0, top: 0, width: size, height: size }, { wordmark: false });
}
await check('apple-touch-icon.png', variants['logo-mark'], { left: 16, top: 16, width: 148, height: 148 }, { background: 255, wordmark: false });
await check('logo-compact-boxed@2x.png', variants['logo-compact'], compactBoxed.placement, { background: 255 });
const emailSize = await sharp(`${OUT}/logo-email.png`).metadata();
await check('logo-email.png', variants['logo-compact'], { left: 0, top: 0, width: emailSize.width, height: emailSize.height });
await check('og-image.png', variants['logo-full'], ogPlacement, { background: 255 });

if (details.length === 0) {
    failures.push('No small shapes (such as the i-dot) were found in the wordmark row; the check cannot run.');
}

if (failures.length > 0) {
    console.error('brand:build FAILED — the generated logos do not match the master:\n  ' + failures.join('\n  '));
    process.exit(1);
}
console.log(`Checked every variant against the cleaned master (${details.length} small detail(s), including the i-dot).`);

// config/brand.php for templates (sizes in CSS pixels = the 1x files).
const php = (value, indent = '    ') => {
    if (typeof value === 'number') return String(value);
    if (typeof value === 'string') return `'${value.replace(/'/g, "\\'")}'`;
    const inner = Object.entries(value).map(([k, v]) => `${indent}    '${k}' => ${php(v, indent + '    ')},`).join('\n');
    return `[\n${inner}\n${indent}]`;
};
writeFileSync('config/brand.php', `<?php

declare(strict_types=1);

// Generated by \`npm run brand:build\` from ${SOURCE}. Do not edit by hand.
// Sizes are CSS pixels (the 1x files); @2x files have twice as many pixels.

return ${php(brand, '')};
`);

console.log(`Cleared ${cleared} faint pixels; shapes: ${shapes.length} (wordmark ${wordmark.length}, tagline ${tagline.length}).`);
console.log('Variants:', JSON.stringify(brand.variants));

# AviNutra — Design Brief (v1.1, 29 Sep 2026 — SVG master logo added)

**Status:** approved by the owner. This file **supersedes v5 §E1** (brand and design) and the "Visual identity" and imagery parts of Content Blueprint v3 §4. Where they conflict, follow this file.

**Source files** (in `docs/design/`):
- `logo-source.svg` — **master logo (vector)**.
- `logo-source.png` — raster reference copy, 2172×724 px, transparent background; used only for comparison and as a fallback.
- `homepage-mockup.png` — approved homepage mockup, 1536×1024 px.

---

## 1. What the mockup decides, and what it does not

The mockup decides the **look**: layout, colours, typography style, spacing, shapes, button and icon styles, and the "feel" of imagery.

It does **not** decide:
- **Wording.** Copy comes from Content Blueprint v3, with the adjustments in §6 below.
- **Navigation.** The nav items come from v3 §6 (§5 below).
- **Specific photos.** The mockup is one flat image, and its pictures cannot be cut out and reused. Real, licensed images are required (§4).

---

## 2. Logo

### 2.1 The master is the SVG — inspect it first
The SVG is the master. All variants are derived from it, not from the PNG. Before using it, inspect the file and report what you find:

1. **Is it a true vector?** Some "SVG" logos are only a PNG wrapped inside an SVG (an `<image>` element with embedded base64 data), or an automatic trace made of thousands of tiny paths.
   - If it contains an embedded raster, **stop and tell me**. It is not a real vector, and the PNG rules in §2.4 apply instead.
   - If it is an auto-trace with visible noise (stray specks, jagged edges), report the path count and show me a large render before continuing.
2. **Is the text converted to outlines?** The wordmark and tagline must be shapes, not `<text>` that depends on an installed font. If live text is present, tell me which font it names. Do not substitute a font silently.
3. **Colours:** list the fill colours used. If they differ from the §3 tokens by more than a small amount, the **SVG's colours win**. Update the tokens to match and report the change.
4. **Size and hygiene:** check for editor leftovers (Illustrator or Inkscape metadata, hidden layers, embedded fonts, scripts, external references).

### 2.2 Clean and optimise
- Optimise with **SVGO** (add it as an npm **dev** dependency — approved), keeping `viewBox` and removing metadata, editor data, comments and hidden elements. **Never strip or merge shapes in a way that changes how the logo looks.** Compare renders before and after at 2000 px wide; they must be visually identical.
- **Security:** the served SVGs must contain no `<script>`, no event attributes (`onload` etc.), no `<foreignObject>` and no external references. Only these vetted logo files are ever served as SVG; the site never accepts SVG uploads from users.
- Keep the untouched original in `docs/design/logo-source.svg`. The optimised copies go in `public/brand/`.

### 2.3 Variants (all derived from the SVG)

| Variant | Contents | Use | Formats |
|---|---|---|---|
| `logo-full.svg` | Mark + wordmark + tagline | About page, footer on a light background, printable documents | SVG |
| `logo-compact.svg` | Mark + wordmark, **no tagline** (remove the tagline group and tighten the viewBox) | Header — the tagline is illegible below ~320 px wide | SVG |
| `logo-mark.svg` | The "A" with the chicken and leaf only (square viewBox with small padding) | Favicon, CRM panel icon, social avatar | SVG + PNG 512/192/180/32/16 + `favicon.ico` |
| `logo-reverse.svg` | Wordmark recoloured for dark backgrounds: "vi" and the tagline in white, "Nutra" orange kept, the mark unchanged or on a white roundel | Dark-green footer and other dark areas | SVG |
| `logo-email.png` | `logo-compact` rendered at 2× (about 400×110 px) | E-mail templates, because many mail clients block SVG | PNG |
| `og-image.png` | Logo centred on white, 1200×630 | Social sharing previews | PNG |

- **PNG rendering:** make the PNGs from the SVG with a headless renderer (e.g. `sharp` or `@resvg/resvg-js` as an npm **dev** dependency — approved). Commit the generated files, so the server never needs to render them.
- **Build script:** add a script (e.g. `npm run brand:build`) that regenerates every variant from `docs/design/logo-source.svg`. When the logo changes, one command updates everything.
- **If a variant cannot be made cleanly from the SVG** (for example, the tagline is merged into the same path as the wordmark), tell me rather than approximating it.

### 2.4 Usage rules
- **Header:** `<img src="/brand/logo-compact.svg" alt="AviNutra" width=… height=…>` at 40–48 px tall on desktop and 32–36 px on mobile. Use an `<img>` rather than inline SVG, so the browser caches it. Always set width and height to avoid layout shift.
- **Footer:** `logo-reverse.svg` on dark green. If the reverse version doesn't look right, use a light `surface` footer with `logo-full.svg`.
- **Favicon:** `logo-mark.svg` as the SVG favicon, plus the PNG and `.ico` fallbacks and `apple-touch-icon` (180 px).
- **CRM panel (Filament):** `logo-compact.svg` as the brand logo (with a matching dark-mode variant if the panel's dark mode is on) and `logo-mark.svg` as the favicon.
- **The PNG reference file** (`logo-source.png`) is not used on the site. If it is ever needed as a fallback, note that it contains about 18,000 near-transparent stray pixels around "Nutra". They are invisible on white but show as smudges on dark backgrounds, so never place it on a dark background without first clearing pixels with alpha < 40.
- **Never** stretch, recolour (except the approved reverse variant), add effects or shadows to, or rearrange the logo.

---

## 3. Design tokens (replace the v5 palette)

Colours sampled from the PNG logo and the mockup. **Confirm them against the SVG's actual fill values (§2.1 step 3); the SVG wins.**

| Token | Hex | Use |
|---|---|---|
| `green-900` | `#013B32` | Hero headline, darkest text on light backgrounds |
| `green-800` | `#034C33` | Headings, footer background, the "Get in Touch" pill |
| `green-700` (primary) | `#025E3D` | Links, icons, active nav underline, CRM primary colour |
| `green-500` | `#6BA727` | Leaf accent, small highlights, success states |
| `orange-600` | `#FC6B01` | Primary button ("Our Solutions"), small eyebrow labels, hover states |
| `orange-500` | `#FC7804` | Accent icons, short divider lines |
| `orange-400` | `#FD9302` | Top of the "Nutra" gradient; decorative only |
| `ink` | `#1C2226` | Body text |
| `muted` | `#5B6770` | Secondary text |
| `surface` | `#F7F8F6` | Section backgrounds, cards |
| `line` | `#E3E7E4` | Dividers, card borders |

- **Brand gradient** (decorative only; never behind body text): `linear-gradient(180deg, #FD9302, #FC6B01)`.
- **Contrast:** white text on `orange-600` is borderline for small text. Use it only on large, bold button labels (≥ 18 px bold) or put dark text on orange. Verify every text/background pair meets WCAG AA.

**Typography**
- **Headings:** a bold, rounded-humanist sans close to the mockup. Propose two self-hosted options (e.g. *Nunito Sans ExtraBold* or *Lato Black*), show both on the hero, and let the owner choose. Hero headline about 56–64 px desktop / 36–40 px mobile, tight line-height (1.05–1.1).
- **Body:** Inter (already installed), 17–18 px, line-height 1.6.
- **Article headings:** Source Serif 4 may stay.
- Everything self-hosted; no external font URLs.

**Shape and components**
- Buttons are fully rounded pills.
  - **Primary:** orange fill, white bold text, → arrow.
  - **Secondary:** transparent with a 2 px dark-green outline.
  - **Header CTA:** dark-green fill.
- **Feature strip icons:** thin-line icons in a circular outline, in green or orange. Use the Heroicons already bundled with Filament, or another self-hosted MIT-licensed line-icon set.
- **Cards:** white or `surface`, radius 16–20 px, very soft shadow, generous padding.
- **Dividers:** thin vertical lines between feature-strip items on desktop; stacked on mobile.
- **Image frames:** circular and leaf-shaped crops (SVG `clip-path`), as in the hero collage.
- **Eyebrow labels:** small uppercase orange text above a section title (e.g. "OUR PURPOSE"), letter-spacing 0.08em.
- **Short accent bars:** 40 px × 3 px orange lines under small headings (as under "GLOBAL INGREDIENTS / LOCAL IMPACT").

---

## 4. Imagery (this replaces the v5/v3 "no stock photos" rule)

The owner wants photographic imagery as in the mockup: chickens, chicks, feed ingredients, a world map, a port/ship. Rules:

1. **Every image must be properly licensed.** Use the owner's own photos, or free-licence sources whose licence allows commercial use without attribution (e.g. Unsplash, Pexels). Record every image's source URL, author and licence in `docs/design/IMAGE-CREDITS.md`. **No images copied from other companies' websites. No AI images of real brands, bags or logos.**
2. **Reflect what AviNutra actually sells.** The mockup shows soybeans and maize, but AviNutra supplies feed additives (amino acids, enzymes, specialty additives), not grain. Use grains only as general "feed" context, and include at least one additive-relevant image: fine white powder or granules, a lab/QC scene, or sample bags **without** brand markings.
3. **Poultry type:** prefer white commercial broilers and chicks, which are AviNutra's core market. The red-combed hen in the mockup is acceptable as a stylised hero.
4. **Performance:**
   - WebP/AVIF with `srcset`;
   - the hero image ≤ 200 KB on mobile;
   - everything below the fold lazy-loaded;
   - explicit width and height on every image, to avoid layout shift.
5. **Until approved images are in place:** use the brand shapes (circles, leaves, gradient panels, the world-map outline as an SVG) without photos. Never publish grey placeholder boxes. List the missing images in `CONTENT-GAPS.md`.

---

## 5. Header and navigation

- **Layout as in the mockup:** logo left; nav centre-right; search icon; a dark-green pill CTA on the right. The white header becomes sticky with a subtle shadow on scroll. The active item has a green underline.
- **Nav items** — use the v3 structure, with the mockup's friendlier labels where equivalent:

| Mockup label | Use instead (v3) |
|---|---|
| About Us | **About** |
| Our Solutions / Consulting | **Nutrition Services** (one item; consulting lives here) |
| Ingredients | **Ingredients** |
| Global Supply | **For Suppliers** — keep this; it is essential for manufacturers |
| Insights | **Insights** (= the Knowledge centre; the label "Insights" is fine) |
| — | **Tools** — keep; the calculators are a key credibility feature |
| — | **Quality** — keep |
| Contact | **Contact** |
| Get in Touch (CTA) | **Ask a Nutritionist** or **Get in Touch** — owner's choice; default "Get in Touch", linking to Contact with the enquiry-type picker |

- **Search icon:** show it only once site search exists (Phase 1). Until then, hide it.
- **Mobile:** hamburger menu → full-height drawer; the CTA pinned at the bottom of the drawer.

---

## 6. Homepage — layout from the mockup, wording from v3 (with fixes)

Section order, top to bottom:

1. **Hero** — split layout.
   - **Left:** headline, sub-line and two buttons.
   - **Right:** image collage (a bird, circular/leaf frames with ingredient textures, a world-map panel with "GLOBAL INGREDIENTS / LOCAL IMPACT" and an orange accent bar, a port/ship scene).
   - **Headline — owner to choose one** (default A):
     - A: **"Poultry Nutrition Expertise. Global Feed Ingredient Supply."** (v3)
     - B: **"Nutrition for Stronger, More Efficient Poultry"** (a mockup-style variant)
     - Do **not** use "Healthier" — keep to nutritional and performance language, not health claims.
   - **Sub-line:** "International feed nutrition, consulting and ingredient supply for efficient and profitable poultry production."
   - **Buttons:** **Our Services →** (orange, to Nutrition Services) · **Contact Us** (outline).
2. **Feature strip** — four items with circular line icons and vertical dividers:
   - **Feed Nutrition** — Science-based formulation support
   - **Consulting** — Practical guidance for profitable production
   - **Ingredient Supply** — Global sourcing with documented quality
   - **International Reach** — Connecting global producers with Pakistan and beyond
3. **Audience split** (from v3; not in the mockup — add it here): two cards, "I run or supply a feed mill →" and "I manufacture feed ingredients →".
4. **Purpose block** — as in the mockup: an image on the left (a chick on feed), and a card overlapping it:
   - eyebrow **OUR PURPOSE**;
   - title **"Better Nutrition. Stronger Poultry. Better Economics."** (replaces "A Healthier Tomorrow");
   - text: "At AviNutra, we combine technical nutrition expertise, reliable ingredient sourcing and practical consulting to help feed manufacturers make better nutritional and commercial decisions."
5. **Three value pillars** (right of or below the purpose block, with orange/green line icons). The mockup's wording claims things not yet true, so use these truthful versions:

| Mockup (do not use) | Use |
|---|---|
| Quality Ingredients — Sourced from trusted global partners | **Documented Quality** — Specifications and certificates you can verify |
| Global Network — Serving clients across key markets | **Global Sourcing** — Established international manufacturers, focused on Pakistan |
| Expert Team — Industry knowledge you can rely on | **Technical First** — Recommendations made or reviewed by feed nutritionists |

6. Then continue with the v3 Home sections, restyled in this visual language:
   - "Feed Mills Need More Than a Supplier" with the Nutrition → Product → Economics → Supply → Performance flow;
   - Why AviNutra (6 pillars);
   - Precision Amino Acid Nutrition, with a calculator teaser;
   - Latest Insights (hidden until an article exists);
   - the closing CTA band on dark green.
7. **Footer** — dark green (`green-800`) with `logo-reverse.svg`; fall back to a light `surface` footer with `logo-full.svg` if the reverse version doesn't look right. Four columns: About/tagline · Services · Resources (Tools, Insights, Quality) · Contact (e-mails; WhatsApp when set). The status statement and legal links go in the bottom bar.

**Other pages** use the same system: a smaller hero band (title + breadcrumb + a thin image strip or brand shape), cards, line icons, eyebrow labels, and pill buttons.

---

## 7. Acceptance for the design step

- [ ] At 1440 px, the homepage matches the mockup's layout, spacing, colours and component styles. Wording follows §6.
- [ ] At 390 px mobile: the hero stacks with the text first, then one simplified image; the feature strip stacks; nothing overflows horizontally.
- [ ] Lighthouse mobile ≥ 90; all colour pairs pass WCAG AA; no external font or image URLs.
- [ ] The SVG inspection report (§2.1) has been given to me: true vector or not, text outlined or not, colours found, and size before and after SVGO.
- [ ] All logo variants are generated from the SVG by `npm run brand:build`. Optimised files are visually identical to the original at 2000 px, and none contains scripts, event attributes or external references.
- [ ] `IMAGE-CREDITS.md` lists every photo with its licence; `CONTENT-GAPS.md` lists any missing photos.
- [ ] The CRM panel uses the green primary colour, `logo-compact.svg` and the `logo-mark` favicon.
- [ ] Screenshots at 1440 px and 390 px are saved to `docs/design/screens/` for owner review.

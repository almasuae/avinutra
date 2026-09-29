# AviNutra — Design Brief (v1.2, 29 Sep 2026 — PNG is the logo master)

**Status:** approved by the owner. This file **supersedes v5 §E1** (brand and design) and the "Visual identity" and imagery parts of Content Blueprint v3 §4. Where they conflict, follow this file.

**Source files** (in `docs/design/`):
- `logo-source.png` — **master logo**, 2048×682 px, transparent background (owner's decision, 29 Sep 2026; see §2.1).
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

### 2.1 The master is the PNG
**Owner's decision (29 Sep 2026):** `docs/design/logo-source.png` (2048×682 px, transparent background) is the logo master. The earlier "SVG" was only a wrapper around this same PNG and has been removed. A vector will be commissioned only if large-format print is needed.

All variants are derived from the PNG by `npm run brand:build`.

### 2.2 Clean-up
The PNG contains about 67,000 near-transparent stray pixels (alpha 1–39), mostly around "Nutra". They are invisible on white but show as smudges on dark backgrounds. The build therefore sets every pixel with **alpha < 40 to fully transparent** before anything else. Nothing else is changed: no recolouring, no sharpening, no reshaping. The cleaned master is written to `public/brand/logo-clean.png`.

### 2.3 Variants (all derived from the cleaned PNG)

| Variant | Contents | Use | Files (in `public/brand/`) |
|---|---|---|---|
| `logo-full` | Mark + wordmark + tagline | About page, light footer, printable documents | PNG + WebP, 1× and 2× |
| `logo-compact` | Mark + wordmark, **no tagline** (the tagline line cropped off) | Header — the tagline is illegible below ~320 px wide | PNG + WebP, 1× and 2× |
| `logo-mark` | The "A" with the chicken and leaf only (cut out along its own outline, so no part of the "v" is included; square canvas with small padding) | Favicon, CRM panel icon, social avatar | PNG + WebP, 1× and 2× |
| Icons | `logo-mark` on a square | Browser and home-screen icons | `favicon-16.png`, `favicon-32.png`, `favicon.ico` (16 + 32), `apple-touch-icon.png` (180, white background), `icon-192.png`, `icon-512.png` |
| `logo-compact-boxed` | `logo-compact` on a white rounded panel | CRM panel in dark mode | PNG, 2× |
| `logo-email.png` | `logo-compact`, 400 px wide (shown at 200 px) | E-mail templates | PNG |
| `og-image.png` | `logo-full` centred on white, 1200×630 | Social sharing previews | PNG |

- The build uses `sharp` (npm **dev** dependency). The generated files are committed, so the server never renders them.
- It also writes `config/brand.php` with each variant's pixel size, so templates can set `width` and `height` without guessing.
- There is **no reverse (white) version**: a raster wordmark cannot be recoloured cleanly. On dark backgrounds, the full-colour logo sits on a white rounded panel.

### 2.4 Usage rules
- **Header:** `logo-compact` at 2× resolution: `<img src="/brand/logo-compact@2x.png" … width=… height=…>` (WebP via `<picture>`), displayed 40–48 px tall on desktop and 32–36 px on mobile. Always set width and height to avoid layout shift.
- **Footer:** dark green (`green-800`) with the full-colour `logo-full` on a white rounded panel (owner's choice, option B).
- **Favicon:** `favicon.ico`, `favicon-32.png`, `favicon-16.png` and `apple-touch-icon.png`; `icon-192.png` and `icon-512.png` in the web manifest.
- **CRM panel (Filament):** `logo-compact` as the brand logo (`logo-compact-boxed` in dark mode) and the `logo-mark` favicon.
- Only the cleaned files in `public/brand/` are used on the site; never the raw `logo-source.png`.
- **Never** stretch, recolour, add effects or shadows to, or rearrange the logo.

---

## 3. Design tokens (replace the v5 palette)

Colours sampled from the PNG logo and the mockup, and confirmed against the logo master in the design step (the logo's greens and oranges are within a few units of these values).

| Token | Hex | Use |
|---|---|---|
| `green-900` | `#013B32` | Hero headline, darkest text on light backgrounds |
| `green-800` | `#034C33` | Headings, footer background, the "Get in Touch" pill |
| `green-700` (primary) | `#025E3D` | Links, icons, active nav underline, CRM primary colour |
| `green-500` | `#6BA727` | Leaf accent, small highlights, success states |
| `orange-600` | `#FC6B01` | Primary button ("Our Solutions"), small eyebrow labels, hover states |
| `orange-500` | `#FC7804` | Accent icons, short divider lines |
| `orange-400` | `#FD9302` | Top of the "Nutra" gradient; decorative only |
| `orange-cta` | `#F06501` | Primary button fill: white 19 px bold labels reach 3.2:1 (added for WCAG AA; white on `orange-600` is only 2.9:1) |
| `orange-text` | `#B94E01` | Small orange text such as eyebrow labels: 5.1:1 on white, 4.8:1 on `surface` (added for WCAG AA) |
| `ink` | `#1C2226` | Body text |
| `muted` | `#5B6770` | Secondary text |
| `surface` | `#F7F8F6` | Section backgrounds, cards |
| `line` | `#E3E7E4` | Dividers, card borders |

- **Brand gradient** (decorative only; never behind body text): `linear-gradient(180deg, #FD9302, #FC6B01)`.
- **Contrast:** white text on `orange-600` is borderline for small text. Use it only on large, bold button labels (≥ 18 px bold) or put dark text on orange. Verify every text/background pair meets WCAG AA.

**Typography**
- **Headings:** **Lato Black (900)**, self-hosted (owner's choice after comparing it with Nunito Sans ExtraBold on the hero). Hero headline about 56–64 px desktop / 36–40 px mobile, tight line-height (1.05–1.1).
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
7. **Footer** — dark green (`green-800`) with the full-colour logo on a white rounded panel (owner's choice, §2.4). Four columns: About/tagline · Services · Resources (Tools, Insights, Quality) · Contact (e-mails; WhatsApp when set). The status statement and legal links go in the bottom bar.

**Other pages** use the same system: a smaller hero band (title + breadcrumb + a thin image strip or brand shape), cards, line icons, eyebrow labels, and pill buttons.

---

## 7. Acceptance for the design step

- [ ] At 1440 px, the homepage matches the mockup's layout, spacing, colours and component styles. Wording follows §6.
- [ ] At 390 px mobile: the hero stacks with the text first, then one simplified image; the feature strip stacks; nothing overflows horizontally.
- [ ] Lighthouse mobile ≥ 90; all colour pairs pass WCAG AA; no external font or image URLs.
- [ ] The cleaned logo (alpha < 40 cleared) has been shown on white and on dark green (`#034C33`) at large size, with no specks.
- [ ] All logo variants are generated from the cleaned PNG by `npm run brand:build`.
- [ ] `IMAGE-CREDITS.md` lists every photo with its licence; `CONTENT-GAPS.md` lists any missing photos.
- [ ] The CRM panel uses the green primary colour, `logo-compact` and the `logo-mark` favicon.
- [ ] Screenshots at 1440 px and 390 px are saved to `docs/design/screens/` for owner review.

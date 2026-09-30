# Header / hero options — decision

**Chosen: option A** (owner's decision, 1 October 2026), on every public page. Options B
(warm grey), C (soft gradient with leaf lines) and D (header edge only) were removed from
the code, and their screenshots deleted. The final look is in `../hero-A-final/`.

| Option A | |
|---|---|
| Hero band | pale green `#F2F7EF` on the top section of every public page |
| Header | 1 px line `#E3E7E4`; soft shadow once the page scrolls (sticky header) |

Files: `option-A-{home,services}-{1440,390}.webp` and `option-A-home-1440-scrolled.webp`
(the sticky header with its shadow).

**One setting:** `'hero_style'` in `config/site.php` (or `SITE_HERO_STYLE` in `.env`):
`A` (default) or `original` (the white → warm-grey look before the decision). The styles
are CSS variables in `resources/css/app.css`; every page's top section is
`<x-page.hero-band>`, and `tests/Feature/HeroStyleTest.php` checks every public page.

**Contrast (WCAG AA, `tests/Unit/BrandPaletteTest.php`):** on `#F2F7EF` the orange eyebrow
is 4.65:1, body text 14.8:1, breadcrumb grey 5.34:1, headings 11.6:1.

# Header / hero options (for the owner's choice)

Each option applies to the home hero **and** the inner-page hero band (breadcrumb, eyebrow,
H1, intro). The screenshots show the top of the page (header + hero) at 1440 × 900 and
390 × 844.

| Option | Hero background | Header |
|---|---|---|
| **A** — pale green tint | `#F2F7EF` | 1 px line `#E3E7E4`; soft shadow when scrolled |
| **B** — warm grey tint | surface `#F7F8F6` | 1 px line; soft shadow when scrolled |
| **C** — soft gradient | `#F2F7EF` (top left) → white (bottom right), faint diagonal leaf lines | 1 px line; soft shadow when scrolled |
| **D** — header edge only | white | 1 px line and soft shadow always |

Files: `option-{A,B,C,D}-{home,services}-{1440,390}.webp`, plus
`option-A-home-1440-scrolled.webp` (sticky header with its shadow over the page; the
shadow is the same for A, B and C, and always on for D).

**Choosing:** set `'hero_style' => 'A'` (or B, C, D) in `config/site.php`, or
`SITE_HERO_STYLE=A` in `.env` (then `php artisan config:cache`). Until then the site keeps
its current look (`current`). On a local installation, add `?hero=A` to any URL to preview.

**Contrast (WCAG AA, checked in `tests/Unit/BrandPaletteTest.php`):** on `#F2F7EF` the orange
eyebrow is 4.65:1, body text 14.8:1, breadcrumb grey 5.34:1, headings 11.6:1. In C the leaf
lines are kept at 1.5 % opacity so the eyebrow stays at 4.55:1 where it crosses a line. B
uses the existing surface colour (eyebrow 4.8:1).

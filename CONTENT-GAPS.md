# Content gaps

Facts the public site does not yet have. For each one the site either **omits** the
element or uses **neutral, truthful wording** — never a placeholder. Each gap is
also seeded as a CRM task assigned to the first admin by `Database\Seeders\ContentGapTaskSeeder`
(run by `php artisan db:seed`; run it again after `lite-crm:create-admin` to assign the tasks).

When a gap is filled: update the setting or record in the CRM, check the page,
and move the row to "Resolved" with the date.

Since 29 Sep 2026 the public website names no country and shows no company-status
details (owner's decision). Gaps 1 and 2 therefore affect CRM quotations only.

## Open

| # | Page(s) | What is missing | How the site handles it now | Supplied by |
|---|---|---|---|---|
| 1 | CRM quotations (contracting entity) | Local partner's legal name, city and role (Site settings) | Quotations use the contracting entity from Site settings; nothing is shown on the public site | Owner |
| 2 | CRM quotations (contracting entity) | Company incorporation: legal name, company number, registered office (Site settings) | Quotations use the partner until incorporation; nothing is shown on the public site | Owner / corporate secretary |
| 3 | Contact, mobile sticky button, CTA bands | WhatsApp Business numbers (sales, nutrition) | Buttons hidden | Owner |
| 4 | About › Team, article bylines | Team and nutrition-panel profiles with signed consent | Team page not linked; About says profiles "will be published shortly" | Owner / panel members |
| 5 | Ingredients, product pages | Which products are `available`, `on request` or `information` | Products default to `information` (educational only) | Owner |
| 6 | Product pages | Written manufacturer permissions to be named | "International manufacturer — details on request" | Owner |
| 7 | Tools › Methionine Value Calculator | Nutrition-panel approval of the value-factor presets (CRM › Website › Calculator defaults) | Presets labelled "Indicative default" | Nutrition panel |
| 9 | Legal pages | Review by a qualified lawyer | Published as sensible drafts | Owner / lawyer |
| 10 | Knowledge | Authors and reviewers for articles 2–8 | Articles kept as CRM drafts | Nutrition panel |
| 13 | Home hero, purpose block, page hero strips | Approved, licensed photos (poultry, feed ingredients, an additive/QC scene, port/ship) with credits in `docs/design/IMAGE-CREDITS.md` (Design Brief §4) | Brand shapes only (circles, leaves, gradient panels, an abstract globe); no photos, no grey boxes | Owner |
| 14 | Ingredients › Methionine, the methionine article, Tool 1 | Nutrition-panel review of the methionine guide, and a source list for the MHA-FA value factors (0.65 ≈ 75%, 0.70 ≈ 80%, 0.88 = 100% equimolar) | The guide cites the two verified EFSA opinions; the factors are shown as assumptions, marked "Indicative default" | Nutrition panel |
| 15 | Legal pages | Legal review once the company is incorporated: data controller, company details and any law-specific wording | The data controller is "AviNutra (info@avinutra.com)"; the pages refer to "applicable data-protection laws" | Owner / lawyer |

## Resolved

| # | Page(s) | What was missing | Resolved on |
|---|---|---|---|
| 8 | For Suppliers › country market page | Sourced market figures for a country page. Dropped: the public site names no country (owner's decision). | 29 Sep 2026 |
| 11 | Header, favicon, CRM, e-mails | Logo master. Decision: PNG is the logo master; a vector will be commissioned only if large-format print is needed. | 29 Sep 2026 |
| 12 | Enquiry acknowledgement e-mail | The response time AviNutra commits to: "within one working day", now the Site Setting `enquiry_response_time` (editable in CRM › Website › Site settings) | 29 Sep 2026 |

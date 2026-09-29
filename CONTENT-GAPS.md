# Content gaps

Facts the public site does not yet have. For each one the site either **omits** the
element or uses **neutral, truthful wording** — never a placeholder. Each gap is
also seeded as a CRM task assigned to the first admin by `Database\Seeders\ContentGapTaskSeeder`
(run by `php artisan db:seed`; run it again after `lite-crm:create-admin` to assign the tasks).

When a gap is filled: update the setting or record in the CRM, check the page,
and move the row to "Resolved" with the date.

## Open

| # | Page(s) | What is missing | How the site handles it now | Supplied by |
|---|---|---|---|---|
| 1 | Footer, About › Company, Contact, quotations | Pakistani partner's legal name, city and role | Status statement without partner name | Owner |
| 2 | Footer, About › Company, Contact, legal pages | Singapore incorporation: legal name, UEN, registered office | "Being established in Singapore" wording; no "Pte. Ltd." | Owner / corporate secretary |
| 3 | Contact, mobile sticky button, CTA bands | WhatsApp Business numbers (sales, nutrition) | Buttons hidden | Owner |
| 4 | About › Team, article bylines | Team and nutrition-panel profiles with signed consent | Team page not linked; About says profiles "will be published shortly" | Owner / panel members |
| 5 | Ingredients, product pages | Which products are `available`, `on request` or `information` | Products default to `information` (educational only) | Owner |
| 6 | Product pages | Written manufacturer permissions to be named | "International manufacturer — details on request" | Owner |
| 7 | Tools 1 and 2 | Nutrition-panel approval of calculator defaults | Defaults labelled "Indicative default" | Nutrition panel |
| 8 | For Suppliers › Pakistan market | Sourced, dated aggregate market figures | Page not published or linked | Owner |
| 9 | Legal pages | Review by a qualified lawyer | Published as sensible drafts | Owner / lawyer |
| 10 | Knowledge | Authors and reviewers for articles 2–8 | Articles kept as CRM drafts | Nutrition panel |
| 13 | Home hero, purpose block, page hero strips | Approved, licensed photos (poultry, feed ingredients, an additive/QC scene, port/ship) with credits in `docs/design/IMAGE-CREDITS.md` (Design Brief §4) | Brand shapes only (circles, leaves, gradient panels, an abstract globe); no photos, no grey boxes | Owner |
| 14 | Ingredients › Methionine, Tool 1 | Nutrition-panel review of the methionine guide, and a source list for the MHA value factors offered in Tool 1 (0.65 / 0.75 / 0.88) | The guide cites only the two verified EFSA opinions and presents the value factors as assumptions the reader chooses | Nutrition panel |

## Resolved

| # | Page(s) | What was missing | Resolved on |
|---|---|---|---|
| 11 | Header, favicon, CRM, e-mails | Logo master. Decision: PNG is the logo master; a vector will be commissioned only if large-format print is needed. | 29 Sep 2026 |
| 12 | Enquiry acknowledgement e-mail | The response time AviNutra commits to: "within one working day", now the Site Setting `enquiry_response_time` (editable in CRM › Website › Site settings from Phase 7) | 29 Sep 2026 |

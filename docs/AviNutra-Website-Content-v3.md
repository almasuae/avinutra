# Website Content Blueprint — v3
## AviNutra · International Poultry Feed Nutrition, Consulting & Ingredient Supply
**Domain:** AviNutra.com · **Version:** 3.0 · 28 September 2026 · **Supersedes:** Prompt-Feed-Website-v2.md

**Companion document:** `Prompt-AviNutra-Laravel-Build-v5.md` (technical build).
- **This blueprint** decides *what the website says and shows*.
- **The v5 build prompt** decides *how it is built*: Laravel, Filament, the reusable Lite CRM package, MariaDB, and the Hetzner/HestiaCP deployment.
- Where the two conflict on a technical point, v5 wins.

> **Owner's decisions of 29 September 2026 (they override the sections below):**
> 1. **No country references on the public website.** The site does not mention Singapore, Pakistan (or Pakistani), PKR, Karachi, Lahore, Punjab, Sindh, SBP, FBR or "Pte. Ltd." — in pages, SEO fields, glossary, articles, legal pages or e-mails. The company-status statement (§3), the office and partner details on Contact (§7.10), the "Legal status" on About › Company (§7.2) and the status line in the footer are **not shown**. Company › "Who contracts with you" says only that every quotation states the contracting legal entity. The Pakistan Market Overview (§7.7) is dropped. Legal pages refer to "applicable data-protection laws" and name the data controller as "AviNutra (info@avinutra.com)". The company-status settings still drive **quotations in the CRM**, and the CRM keeps its territories, currencies and partner data.
> 2. **Tool 2 is universal:** "Landed Cost Calculator" at `/tools/landed-cost`, any currencies, any duty and tax lines, blank defaults with an "Example" button (§7.8 Tool 2 is replaced). Tool 1 takes any currency plus an exchange-rate input instead of a USD/PKR switch.
> 3. **Tool 1 MHA-FA presets (product basis):** 0.65 (about 75% equimolar), 0.70 (about 80%, the EFSA-cited meta-analysis) and 0.88 (100% equimolar, the manufacturers' position), shown as "Indicative default" until the nutrition panel approves them.
> 4. **Tools index:** live tools plus at most two "Coming soon" cards (Feed Cost Impact, FCR Economics).

---

# 0. How to use this blueprint

**Your role (the builder):** you are a senior Laravel developer and B2B content strategist. You write production-quality code (per v5) and draft copy that the owner and the nutrition panel will review and improve over time.

**Rules you must follow throughout:**

1. **The site is live and public from day one.** There is no preview or holding mode. Shortcomings are addressed gradually after launch.
2. **Never invent facts.** Do not create team members, qualifications, clients, testimonials, trial results, statistics, certifications, awards, years in business, office addresses, phone numbers, product availability or manufacturer partnerships.
3. **Never show placeholders publicly.** Where a fact is missing, handle it one of three ways:
   - **omit** the element (e.g. no Team page until a profile with consent exists; no WhatsApp button until a number is set; no "Latest articles" block until an article is published);
   - use **neutral, truthful wording** (e.g. "Details available on request", "Our team profiles will be published shortly");
   - list the gap in `CONTENT-GAPS.md` **and** create it as a Task in the CRM.
4. **No manufacturer names, logos or trademarks** on the public site (e.g. MetAMINO®, Rhodimet®, Sandimet®) unless written permission is recorded against that manufacturer in the CRM. Generic product names (DL-Methionine, Phytase) are always fine.
5. **No therapeutic or disease claims** ("prevents", "cures", "treats"). Use nutritional language ("supports", "contributes to", "is used to supply").
6. **Every market figure, price trend or supply event** published on the site must show its source and date.
7. **Technical articles written for publication under a person's name** stay in *draft* or *in review* in the CRM until that person has authored or reviewed them. General technical pages that the builder drafts are published as company content, written cautiously, and improved later.

---

# 1. The core idea

> **Not:** "A trading company that happens to know nutrition."
> **But:** "A feed-nutrition company that uses international sourcing and trading capability to deliver nutrition solutions."

> **The website must convince manufacturers and suppliers that engaging AviNutra will build their sales and their brand in Pakistan and the wider region — and convince feed mills that AviNutra understands their business better than an importer selling bags.**

**Background for the builder** (internal context — do not publish these figures unless sourced as in rule 6): AviNutra is a specialist feed-nutrition and ingredient-sourcing company being established in Singapore. Its initial commercial focus is Pakistan's poultry feed industry: customs data for April–August 2026 shows roughly 35,000 t/yr of methionine imported by about 110 feed mills. Its future footprint is South Asia, Southeast Asia and MENA. It is led by a nutrition advisory panel and supported by an international sourcing and trading function.

---

# 2. Audiences and what each must conclude

| Audience | What they should conclude within two minutes | Their main path |
|---|---|---|
| **Manufacturers / suppliers** | "They understand feed nutrition → they understand the Pakistani market → they have technical people who can support customers → they have a real commercial plan → they could build our brand in Pakistan." | Home → For Suppliers → About / Company → Partner application |
| **Feed mills** (nutritionists, purchase managers, owners) | "These people understand my formulation, procurement and cost problems." | Ingredient or article → Calculator → Ask a Nutritionist → Sample → Trial → Quote |
| **Integrated poultry producers** | "They can help me reduce feed cost per kg of live weight." | Nutrition Services → Feed economics → Contact (FCR tool from Phase 1) |
| **Nutritionists / technical readers** | "This is a serious technical source." | Methionine hub → Calculator → Article → Consultation |
| **Banks, LC counterparties, prospective partners** | "This is a real, transparent, professionally run business." | About → Company → Contact |

---

# 3. Company status and transparency

The site is live **before** the Singapore entity, bank accounts and LC lines are operational. It must build credibility without overstating anything.

**Controlled by Settings in the CRM** (v5 §A5), never by hard-coded text. The settings are: `sg_incorporated`, `legal_name`, `uen`, `registered_office`, `pk_partner_name`, `pk_partner_city`, `pk_partner_role`.

**Status statement variants** (shown in the footer, on About › Company, on Contact and on quotations):

| Situation | Wording |
|---|---|
| Not incorporated, partner name set | "Our international trading company is being established in Singapore. Until incorporation is complete, commercial activity in Pakistan is conducted through our partner, {partner}, {city}." |
| Not incorporated, no partner name yet | "Our international trading company is being established in Singapore, with commercial activity initially focused on Pakistan." |
| Incorporated | "{legal_name} (UEN {uen}) is incorporated in Singapore." — and, when a partner is set: "In Pakistan, products are imported and supplied through {partner}." |

**Never claim, until it is true and documented:**
- incorporation, or the suffix "Pte. Ltd.";
- licences or registrations;
- warehouses or stock held;
- exclusive or appointed distributorships;
- banking or LC facilities;
- certifications held by AviNutra itself;
- named customers.

**Contracting entity.** Every quotation (generated in the CRM) and the Contact page state which legal entity is contracting.

**Commercial-relationship disclosure.** AviNutra will represent specific manufacturers, so the site says "objective", never "independent". The following disclosure appears on About › Company and on the Editorial Policy page:

> *"We have commercial relationships with some of the manufacturers whose products we supply. Our technical evaluations state the basis of any comparison, and we will tell you when we are recommending a product we sell."*

---

# 4. Brand positioning and voice

- **Brand:** AviNutra
- **Primary statement:** Poultry Feed Experts · Nutrition Consultants · Feed Ingredient Suppliers
- **Tagline (selected):** *Where Feed Science Meets Reliable Supply.*
- **Supporting proposition:** *Technical nutrition expertise. Reliable global sourcing. Practical feed solutions.*
- **Alternates** (for campaigns or social media): *Science Behind the Feed. Reliability Behind the Supply.* · *From Formulation to Supply.* · *Nutrition Expertise. Global Ingredients. Better Feed Decisions.*

## Visual identity (per v5 §E1)

- **Wordmark:** "AviNutra" set in Inter semibold, with "Avi" in the primary colour, plus a simple abstract mark (a feather, or a leaf with a droplet).
- **Palette:**

| Token | Hex | Notes |
|---|---|---|
| Primary | `#1F4E5F` | |
| Primary-dark | `#163A47` | |
| Accent | `#C8A24A` | Use sparingly |
| Ink | `#1C2226` | Body text |
| Muted | `#5B6770` | Secondary text |
| Surface | `#F6F8F8` | Section backgrounds |
| Line | `#DDE3E5` | Borders |

- **Fonts:** Inter (UI) and Source Serif 4 (article headings), both self-hosted.

## Voice

- British English, familiar to Pakistani and Singaporean readers.
- Plain, precise and confident. Explain each technical term on first use or link it to the glossary.
- Prefer numbers to adjectives: write "cost per kg of effective methionine", not "best value".

## Visual credibility model

| Quality | How it shows |
|---|---|
| Scientific | Clean layout, data tables, specifications, cited sources |
| Professional | International B2B look; restrained palette; consistent typography |
| Technical | Calculators, product specifications, technical articles, documentation |
| Human | Real photographs of the real team and advisers, only with written consent (until then, no people photos) |
| Commercial | A clear enquiry route on every relevant page |

**Avoid:** stock poultry or farm photos; cartoon chickens; "best quality" or "world-class" without evidence; fake or implied certifications; unexplained jargon; slogans with no content; images of competitors' branded bags.

---

# 5. Website requirements that affect content

Implementation details are in v5. These are the content-facing requirements:

| Area | Requirement |
|---|---|
| Content management | All editable content (products, articles, team profiles, glossary, FAQs, calculator defaults, tax rates, site settings, page SEO fields) is managed in the CRM's **Website** section |
| Performance | Mobile-first (most Pakistani visitors use phones); Lighthouse mobile ≥ 90; light pages, few images |
| Accessibility | WCAG 2.1 AA: contrast, keyboard use, labelled form fields, accessible calculator results |
| SEO | Indexable from day one; sitemap; canonical URLs; Open Graph; JSON-LD (Organization, Article, Product, FAQPage, BreadcrumbList); `/crm` excluded |
| Languages | English at launch. Built with Laravel localisation so that a Simplified Chinese supplier page (Phase 1) and Urdu/Arabic pages later need no restructuring |
| Search | Simple site search across products, articles and glossary (Phase 1; database-based, no external service) |
| Measurement | No external analytics at launch. Enquiry source pages are captured in the CRM automatically. Optionally count calculator runs, downloads and WhatsApp clicks in the database (privacy-friendly, no cookies). A self-hosted analytics tool can be added later |
| Email | All site email is sent from AviNutra's own mail server (noreply@avinutra.com) |

---

# 6. Information architecture

## Top navigation

**Nutrition Services · Ingredients · Tools · Knowledge · Quality · For Suppliers · About · Contact**, plus a highlighted button **Ask a Nutritionist**.

A page that does not yet exist is **not linked**. There are no dead "coming soon" pages, except that the Tools index may list tools as "in development".

## Site map

Status key: **L** = at launch · **1** = Phase 1 · **2** = Phase 2 · **3** = Phase 3 · **C** = appears when content exists.

```text
/                                        L  Home
/nutrition-services                      L  Overview
  /feed-mills                            L  Solutions for Feed Manufacturers
  /poultry-producers                     1  Solutions for Integrated Producers
  /formulation-support                   L
  /ingredient-evaluation                 L
  /product-substitution                  L
  /feed-economics                        L
  /supplier-qualification                L
  /technical-trials                      L
  /request-sourcing                      L  Form
/ingredients                             L  Directory (from CRM Products marked "publish on website")
  /amino-acids/methionine                L  Methionine hub (flagship)
  /amino-acids/{lysine|threonine|valine} 1
  /{category}/{product}                  C  Product page template
/tools                                   L  Index
  /methionine-value                      L
  /landed-cost-pakistan                  L
  /feed-cost-impact                      1
  /fcr-economics                         1
  /amino-acid-value                      2
  /methionine-requirement                2
  /formulation-explorer                  3
/knowledge                               L  Article list (published articles only)
  /{slug}                                C
  /glossary                              L
  /faqs                                  1
  /market-watch                          1–2
  /guides                                1  Downloads (requested through a form, so each becomes a CRM enquiry)
/quality                                 L
/suppliers                               L  Partner With Us
  /how-we-work                           L
  /apply                                 L  Supplier application
  /pakistan-market                       C  Once sourced aggregate figures are supplied
  /zh                                    1  Simplified Chinese summary
/about                                   L
  /company                               L  Legal status, Pakistan partner, disclosure
  /editorial-policy                      L
  /team                                  C  When ≥ 1 profile is published with consent
/contact                                 L
/ask-a-nutritionist                      L  Form
/legal/privacy · /legal/terms · /legal/cookies · /legal/technical-disclaimer   L
```

---

# 7. Page specifications

## 7.1 Home

**Hero**
- **Headline:** Poultry Nutrition Expertise. Global Feed Ingredient Supply.
- **Supporting text:** *We combine practical poultry nutrition expertise with international sourcing of quality feed ingredients, to help feed manufacturers make better nutritional and commercial decisions.*
- **Buttons:** **Talk to a Nutrition Expert** (primary) · **Explore Ingredients** · **Use Feed Calculators**
- **Credibility strip:** *Technical expertise · Global sourcing · Quality focus · Responsive supply*

**Audience split** — two clear routes, directly below the hero:
- *I run or supply a feed mill* → Nutrition Services
- *I manufacture feed ingredients* → For Suppliers

**What we do** — four cards:
- **Nutrition Consulting** — Practical formulation and nutritional support for poultry feed manufacturers.
- **Feed Ingredients** — Sourcing and supply of amino acids, specialty additives and other essential feed ingredients.
- **Technical Evaluation** — Product comparison, substitution assessment, trial support and feed-economics analysis.
- **Strategic Sourcing** — Connecting feed manufacturers with reliable international producers and developing sustainable supply channels.

**The problem we solve** — heading: **Feed Mills Need More Than a Supplier.**
> Feed ingredient procurement is no longer a matter of finding the lowest quoted price. Feed manufacturers need consistent quality, reliable supply, technical confidence, formulation support and predictable economics. We bring these together through nutrition expertise, international sourcing and practical customer support.

Visual flow: **Nutrition → Product → Economics → Supply → Performance**

**Why AviNutra** — six pillars:
- **Technical** — Recommendations are made or reviewed by feed nutrition professionals.
- **Practical** — Solutions that work in commercial feed production.
- **Objective** — Products are compared on nutritional value, quality, economics and supply reliability, and we disclose our commercial relationships.
- **Global** — We evaluate and source from established international manufacturers.
- **Transparent** — Specifications, documents and the contracting entity are always stated.
- **Responsive** — Fast technical and commercial replies.

**Featured: Precision Amino Acid Nutrition**
> Amino acids are among the most economically important inputs in modern poultry feed. Their selection, specification and inclusion materially influence feed cost, nutrient efficiency and bird performance. We compare amino-acid products not by price per tonne, but by effective nutritional contribution, quality consistency and total cost of use.

Buttons: **Explore Methionine** · **Try the Methionine Value Calculator**

**Latest from the Knowledge Centre** — the three most recent published articles. *This block is hidden while no article is published.*

**Closing call-to-action band** — *Have a formulation or sourcing question?* → **Ask a Nutritionist**. Add **WhatsApp us** once a number is set.

> Do **not** reference any competitor's supply situation on the Home page.

---

## 7.2 About, Company, Editorial Policy and Team

**About** (`/about`)
- **Who we are:** *A specialist feed-nutrition and ingredient-sourcing company helping poultry and animal-feed businesses make better technical and commercial decisions.*
- **Philosophy:** *Technical knowledge before commercial recommendation.*
- **Approach:** **Understand → Evaluate → Validate → Supply → Support**

**Company** (`/about/company`) — the status statement (§3), the Pakistan partner and its role (shown when set), which entity contracts, and the commercial-relationship disclosure.

**Editorial policy** (`/about/editorial-policy`) — who writes, who reviews, how sources are cited, how often content is reviewed, how corrections are handled, and the disclosure. Each article shows *Author · Reviewed by · Last reviewed*. Company-authored pages show *AviNutra Technical Team* and a last-updated date.

**Team** (`/about/team`) — **appears only when at least one profile is published with consent on file in the CRM.** Each profile shows:
- name, photograph and role (Adviser / Employee / Consultant — state which);
- highest qualification, university and year;
- years of poultry/feed experience and areas of specialisation;
- selected publications or professional memberships (with links);
- languages;
- articles authored or reviewed (linked automatically).

Rules: written consent for name, photo and bio; every credential verifiable; no inflated titles; advisers are never presented as employees. Until this page exists, the About page says: *"Our nutrition advisory panel profiles will be published shortly."*

---

## 7.3 Nutrition Services

An overview page, plus one page per service. Each page ends with **Discuss this with a nutritionist** (the Ask-a-Nutritionist form, pre-selected by topic).

| Service | Content |
|---|---|
| **Formulation support** | Broiler, layer and breeder formulation; starter/grower/finisher programmes; least-cost formulation review; nutrient-density assessment; amino-acid balancing |
| **Ingredient evaluation** | Specification, nutritional value, quality, consistency, supplier reliability, landed cost, formulation impact |
| **Product substitution** | "Can Product A replace Product B?" — **Specification → Nutritional equivalence → Inclusion → Cost → Trial → Performance**. Especially relevant after a supply disruption |
| **Feed economics** | Cost per kg of active nutrient, per kg of effective methionine, per tonne of feed, per bird, per kg of live weight |
| **Supplier qualification** | **Identify manufacturer → Documentation → Technical review → Sample → Trial → Commercial evaluation → Supply** |
| **Technical trials** | Protocol: baseline formulation, control and test groups, defined inclusion and duration, performance indicators, statistical and technical review, economic interpretation. Results are published only with the customer's written consent (recorded on the Trial in the CRM) |

**Solutions for Feed Manufacturers** (`/nutrition-services/feed-mills`) — customer journey: **Requirement → Technical assessment → Product selection → Sample → Trial → Quote → Supply → Technical follow-up**

**Request Sourcing Support** (`/nutrition-services/request-sourcing`) — a form (§8). It helps AviNutra discover demand that is not yet in the catalogue.

---

## 7.4 Ingredient directory and product template

**Source of data:** CRM › Products. A product appears on the website only when **publish on website** is ticked.

**Directory filters:**
- **Category:** amino acids, enzymes, vitamins & minerals, mycotoxin management, gut health, specialty additives.
- **Species:** Broiler, Layer, Breeder, Turkey at launch; others hidden until relevant.
- **Form:** Powder, Granular, Liquid.
- **Function:** Amino-acid nutrition, Digestibility, Gut health, Mycotoxin control, Antioxidant, Mineral nutrition.

**Initial categories** (category pages at launch; individual products added as the owner publishes them):
- Amino acids: DL-Methionine, L-Methionine, MHA, Lysine, Threonine, Valine
- Enzymes: Phytase, Xylanase, Protease, multi-enzyme
- Vitamins & Minerals
- Mycotoxin management
- Gut health
- Specialty additives: Choline, Betaine, Organic acids, Antioxidants

**Availability** (CRM field; the default is `information`):

| Availability | Shown to the visitor | Call to action |
|---|---|---|
| `available` | "Available — supplied via {contracting entity}" | Request Quotation · Request Sample |
| `on request` | "Sourced on request" | Request Sourcing Support |
| `information` | Educational page only | Ask a Nutritionist |

Only an Admin or Manager may set `available`, and only after a supply relationship is secured.

**Product page template:**
1. Product name and one-line description
2. Nutritional function
3. Typical specification (table from the manufacturer's current TDS, with the document date)
4. Available forms and packaging
5. Typical applications and inclusion guidance (panel-reviewed; omitted until reviewed)
6. Storage and shelf life
7. Country of origin
8. Manufacturer — shown only if permission is recorded in the CRM; otherwise *"International manufacturer — details on request"*
9. Certifications — shown only when a **verified** document is attached in the CRM, with its certificate number and expiry date
10. Technical documents — request buttons, each creating a CRM enquiry
11. Related tools and articles

Buttons: **Request TDS · Request COA · Request Sample · Request Quotation · Ask a Nutritionist** (shown according to availability).

---

## 7.5 Methionine hub (flagship) — `/ingredients/amino-acids/methionine`

The strongest technical page on the site; published at launch as AviNutra Technical Team content.

1. **What is methionine?** Its role as the first-limiting amino acid in most poultry diets; methionine + cystine.
2. **Methionine sources:** DL-Methionine 99%, L-Methionine (99% and lower-purity grades such as 90%), methionine hydroxy analogue free acid (MHA-FA, liquid, 88%) and its calcium salt (MHA-Ca).
3. **Why specification matters:** purity/assay, moisture or loss on drying, heavy metals and arsenic, physical form, stability, packaging, shelf life, manufacturing consistency, documentation.
4. **DL-Met vs L-Met:** a technically neutral comparison that states what the evidence shows and where it is uncertain.
5. **MHA vs DL-Met:** explain that MHA is a precursor rather than methionine itself, and that its relative efficacy is debated. Show the range of published positions — independent/regulatory estimates vs manufacturers' figures — with sources, and let the reader choose the assumption in the calculator.
6. **How to compare prices properly:** introduce **cost per kg of effective methionine** instead of price per tonne, with a worked example.
7. **Documentation checklist:** COA, TDS, SDS, specification, registration documents, halal certificate, certificate of origin, quality certificates (FAMI-QS / GMP+, ISO), and how to verify each (e.g. check the certificate number on the issuing body's public register).
8. The **Methionine Value Calculator** embedded, plus links to related articles.

Do not name or criticise individual methionine producers on this page.

---

## 7.6 Quality — `/quality`

**Heading:** **Quality Is Part of the Product.**

Present the quality process as **AviNutra's working method and principles**, not as a claim that audits have already been carried out. The steps are:
- supplier qualification and due diligence;
- certificate verification against public registers;
- product specification control;
- COA verification for each batch;
- batch traceability and retention samples;
- independent laboratory testing where appropriate;
- packaging and label inspection;
- shipping documentation and storage conditions;
- complaint handling and product recall procedure.

Wording such as *"Our supplier qualification process includes…"* is acceptable. *"We have audited…"* is not, until it is true.

---

## 7.7 For Suppliers — `/suppliers`

**Heading:** **Partner With Us**

**Proposition:**
> We work with feed-ingredient manufacturers seeking technical market development, qualified distribution and customer support in Pakistan and selected Asian and MENA markets.

**What we offer manufacturers:** market intelligence · customer identification and targeting · technical representation by nutritionists · product trials · registration and documentation coordination · distributor and stockist development · commercial development · customer support · structured market feedback.

**How we work** (`/suppliers/how-we-work`): Product review → Technical documentation → Manufacturer due diligence → Commercial discussion → Territory assessment → Customer targeting → Trial programme → Launch → Market development and reporting.

**Pakistan Market Overview** (`/suppliers/pakistan-market`) — **added once the owner supplies sourced aggregate figures.**
- Content: market size and structure in aggregate (methionine import volume, number of importing mills, product-form mix, duty and tax structure, poultry production growth), each figure sourced and dated; why the market is under-served; and a regulatory pathway summary marked "not legal advice".
- **Never** publish importer names, individual transactions or supplier-by-supplier prices.
- Offer: *"Detailed market analysis is available to qualified manufacturers under NDA."* → Apply.

**Simplified Chinese summary** (`/suppliers/zh`, Phase 1) — a professionally translated one-page version for Chinese producers, reviewed by a native speaker.

**Supplier application** (`/suppliers/apply`) — see §8. Each application enters the CRM's **Supply partnership** pipeline.

---

## 7.8 Tools & Calculators — `/tools`

The calculators are a traffic and credibility engine. Implementation follows v5 §E3:
- Livewire components, with the calculation logic in PHP service classes and Pest tests.
- Defaults and tax rates are edited in CRM › Website › Calculator defaults. Each value carries a source, a date and approved-by; until approved it is labelled "Indicative default".

**Requirements for every tool:**
- Every input is labelled with its unit.
- A **Show working** toggle displays the formulas used.
- A USD/PKR switch, with the exchange rate as an editable input showing its date.
- Results can be shared through the URL; the page has a print-friendly layout.
- A **Discuss your result** button pre-fills the Ask-a-Nutritionist form with the inputs. It sends nothing until the user submits.
- No calculator inputs are stored without a form submission.
- Every tool ends with the Technical Notice (below).

### Tool 1 — Methionine Value Calculator (launch)

Compares 2 to 4 methionine sources on an equal basis.

**Inputs per product:**
- product type (preset or custom);
- price per kg or per MT, with its basis stated (CFR or landed);
- value factor;
- current inclusion (kg per MT of feed) for the reference product;
- monthly feed production (MT).

**Value factor** = the kg of DL-Methionine 99% replaced by 1 kg of the product (DL-Met 99% = 1.00). Presets are to be confirmed by the nutrition panel, each with its source shown:

| Product | Default factor | Notes |
|---|---|---|
| DL-Methionine 99% | 1.00 | Reference |
| L-Methionine 99% | 1.00 | Evidence does not robustly support a premium at equal purity |
| L-Methionine 90% | 0.909 | Purity adjustment only |
| MHA-FA 88% (liquid) | user must choose **0.65 / 0.75 / 0.88** | 0.65 (product basis) and 0.75 (equimolar) reflect independent/regulatory positions; 0.88 reflects the MHA manufacturers' position |
| Custom | user input | "Use the manufacturer's documented value" |

**Formulas:**
- cost per kg of effective methionine = price per kg ÷ value factor
- equivalent inclusion = reference inclusion × (reference factor ÷ product factor)
- cost per MT of feed = equivalent inclusion × price per kg
- differences per MT of feed, per month and per year, and in %

**Reference test values:** 2.36 ÷ 1.00 = 2.360; 1.76 ÷ 0.65 = 2.708; 1.76 ÷ 0.88 = 2.000; 3.79 ÷ 0.909 = 4.169.

**Tool note:** *"This is an indicative economic comparison. Nutritional equivalence depends on the product, the diet and the formulation basis (total or digestible). Confirm with the manufacturer's technical documentation and your nutritionist."*

### Tool 2 — Landed Cost Calculator, Pakistan (launch)

**Inputs:**
- FOB or CFR price, plus freight;
- insurance (% of value);
- exchange rate;
- customs duty, additional customs duty and regulatory duty (%);
- sales tax (%);
- withholding income tax (%) — filer or non-filer, and importer type;
- LC and bank charges (%);
- port, clearing and documentation (PKR per container);
- inland freight;
- financing cost (annual rate × days);
- net kg per container.

**Output:** landed cost per kg and per MT, in USD and PKR — gross, and net of recoverable sales tax and adjustable income tax — with a line-by-line breakdown and "Rates last verified: {date}". The formulas and defaults are in v5 §E3.

**Reference test:** CFR USD 2.80/kg with the defaults → gross ≈ 3.454, net ≈ 2.882 USD/kg.

**Page note:** rates change with each Finance Act and must be confirmed with a clearing agent.

### Tool 3 — Feed Cost Impact Calculator (Phase 1)
Inputs: feed production volume · ingredient price and inclusion · alternative price and inclusion.
Outputs: cost per MT of feed, monthly and annual cost, saving or opportunity.

### Tool 4 — FCR Economics Calculator (Phase 1)
Inputs: feed cost per MT · FCR · live weight · chick cost · other variable costs.
Outputs: feed cost per bird, feed cost per kg of live weight, and the value of a 0.01 or 0.05 FCR improvement.

### Tool 5 — Amino Acid Value Calculator (Phase 2)
Extends Tool 1 to lysine (e.g. lysine HCl vs lysine sulphate on lysine content), threonine and valine, using assays from product documentation.

### Tool 6 — Methionine Requirement Guide (Phase 2)
Indicative digestible Met and Met+Cys by species, bird type and phase, using the nutrition panel's approved methodology and published references. **Do not reproduce breeding-company specification tables without permission** — cite and link them instead.

### Tool 7 — Formulation Explorer (Phase 3)
An educational least-cost formulation demonstrator with a small, licensed or company-owned ingredient nutrient matrix. Launch only after validation by the nutrition panel, and label it as a teaching tool, not a formulation service.

### Technical Notice (on every tool)
> **Technical Notice** — These tools are provided for education and preliminary commercial evaluation. Results depend on product specifications, formulation assumptions, genetics, production conditions and other variables. Final formulation and feeding decisions should be made by qualified animal-nutrition professionals using validated product data. Tax and duty figures are indicative and are not tax advice.

---

## 7.9 Knowledge Centre — `/knowledge`

**Source of data:** CRM › Website › Articles. Article statuses are *draft → in review → approved → published*, and only *published* articles are shown.

**Categories:**
- **Poultry Nutrition:** amino-acid nutrition, ideal protein, digestible amino acids, protein reduction, energy/protein balance.
- **Feed Ingredients.**
- **Feed Economics.**
- **Ingredient Quality:** COA interpretation, specification comparison, sampling, storage, shelf life, contamination risks.
- **Market Watch.**

**At launch:**
- the Glossary (at least 20 terms);
- article 1 below, published as *AviNutra Technical Team*.

The remaining articles are created as drafts in the CRM, with outlines, and published as the panel authors or reviews them:

1. How to Compare Methionine Sources: Cost per kg of Effective Methionine *(launch)*
2. DL-Methionine vs L-Methionine: What Feed Manufacturers Should Know
3. MHA vs DL-Methionine: Understanding the Efficacy Debate
4. How to Read a Feed-Grade Certificate of Analysis
5. How to Evaluate and Qualify a New Feed Ingredient Supplier
6. Methionine in Broiler Nutrition: Requirements and Practical Formulation
7. Managing Feed Ingredient Supply Risk: Alternative Sourcing Without Surprises
8. Landed Cost in Pakistan: What Really Makes Up the Price of an Imported Feed Additive

Each article shows its author and reviewer (or AviNutra Technical Team), sources, last-reviewed date, related tool, and the call to action **Ask Our Nutrition Team**.

**Market Watch** (`/knowledge/market-watch`, Phase 1–2)
- A monthly *Feed Ingredient Market Watch* covering directional trends for methionine, lysine, threonine, vitamins, enzymes and freight; supply conditions; and capacity additions and closures.
- *Supply Alerts* covering plant shutdowns, force majeure, new capacity, and port or shipping disruption.
- Internal source material can come from the CRM's Price log. **Publish only aggregated, directional information — never individual supplier offers.**
- Publish only facts confirmed by a primary source (company statement, regulator, exchange filing) or clearly attributed trade press, with date and link. No speculation about a named company's intentions. Include *"Market commentary, not investment or purchasing advice."*
- An article on a specific producer's capacity change may be published **only** after the change is confirmed by a primary source, is written factually, and has been reviewed by the owner.

**Guides and downloads** (`/knowledge/guides`, Phase 1)
- Company profile · Methionine technical guide · COA checklist · Supplier qualification checklist · Feed ingredient procurement checklist · Ingredient comparison template · Technical trial protocol · Feed-cost spreadsheet.
- Checklists are free to download. Larger guides are requested through a short form, which creates a CRM enquiry with consent recorded.

**Document requests** (TDS, SDS, COA, specification, halal, ISO / FAMI-QS / GMP+, origin, regulatory documents, labels, packaging) are made through the product pages or the Contact form. They create a CRM enquiry of type *document*, and the team sends the documents. Documents are never published openly.

**Glossary and FAQs** — every technical term used on the site; the FAQs carry FAQ schema (Phase 1).

---

## 7.10 Contact — `/contact`

- **Singapore:** "Singapore — office being established" until a registered office is set in Settings.
- **Pakistan:** the partner's name, city and role, once set in Settings.
- The **contracting-entity statement** (§3).
- **Enquiry types** (each routes to a CRM enquiry type and to the matching mailbox): General (info@) · Sales / Quotation (sales@) · Technical / Ask a Nutritionist (nutrition@) · Supplier partnership (partners@) · Sourcing request (sales@) · Sample request (sales@) · Document request (sales@).
- **WhatsApp:** **WhatsApp Sales** and **WhatsApp Nutrition Team** click-to-chat buttons, plus **Request a Call**. These appear only once the numbers are set in Settings. Use shared business numbers, not personal ones; on mobile the button is sticky.

---

## 7.11 Legal pages

**At launch:**
- **Privacy Policy** — Singapore PDPA; applicable Pakistani law; GDPR-aware, because European manufacturers will visit. It explains that enquiry data is held in AviNutra's own CRM on its own server.
- **Terms of Use.**
- **Cookie notice** — informational only, since only essential session cookies are used and there are no tracking cookies.
- **Technical Disclaimer.**
- The **commercial-relationship disclosure** (on About › Company).

The legal pages are published as sensible drafts and reviewed by a qualified lawyer as soon as practical.

**After incorporation:** the legal name, UEN and registered office appear in the footer and wherever Singapore law requires (confirm with the corporate secretary). Terms of Sale and Supplier Terms are added once the contracting entity and its terms are final.

---

# 8. Forms and lead capture

All forms use the Lite CRM enquiry form component (v5 §C6). Every submission:
- creates a **CRM Enquiry** of the stated type, recording the source page;
- notifies the matching mailbox through AviNutra's own mail server;
- sends the visitor an acknowledgement from noreply@avinutra.com.

| Form | Key fields | Uploads | CRM type / mailbox |
|---|---|---|---|
| **Ask a Nutritionist** | Name · Company · Role · Email · WhatsApp · Country/city · Species · Feed type · Question · Current product | Formulation, COA (optional) | ask a nutritionist / nutrition@ |
| **Request Sourcing Support** | Product · Specification · Quantity per month · Annual requirement · Current supplier (optional) · Current price range (optional) · Delivery location · Documents required · Target delivery date | Specification (optional) | sourcing request / sales@ |
| **Request Quotation / Sample / Document** | Product · Quantity · Delivery point · Documents needed | — | quotation, sample or document / sales@ |
| **Supplier Application** | Company · Country · Website · Contact · Product categories · Manufacturing sites · Annual capacity · Existing Pakistan business and current distributor · Export markets · Certifications · Desired territory · Exclusivity expectations · MOQ · Lead time · Payment terms · Technical support · Sample availability | Catalogue, TDS, SDS, COA, certificates, company profile | supplier application / partners@ → Supply partnership pipeline |
| **General contact** | Name · Company · Country · Email · Enquiry type · Message | — | general / info@ |
| **Market Watch updates** (Phase 2) | Email · Company · Role · Interests | — | Contact with consent flag; low-volume mailing from the own server |

**Rules:**
- An explicit consent checkbox linked to the Privacy Policy.
- A clear statement that uploaded supplier documents are kept confidential, with an NDA offered on request.
- Anti-spam without external services: a honeypot field, a minimum fill time and rate limiting.
- The acknowledgement states the expected response time.
- Uploads: at most 10 MB each, PDF/DOCX/XLSX/JPG/PNG only, stored privately.

**One relevant call to action per page type:**

| Page type | Call to action |
|---|---|
| Ingredient | Request Price & Availability (or as set by availability) |
| Service / technical page | Talk to a Nutritionist |
| Supplier pages | Become a Supply Partner |
| Calculator | Discuss Your Result |
| Article | Ask Our Nutrition Team |

---

# 9. SEO and content strategy

**Keyword themes:**
- **Core:** poultry feed ingredients Pakistan · feed additives Pakistan · animal nutrition Pakistan · poultry nutrition consultant · feed ingredient supplier Pakistan · methionine supplier Pakistan · DL-methionine Pakistan · L-methionine Pakistan
- **Technical:** DL-methionine vs L-methionine · MHA vs DL-methionine · methionine cost per kg · methionine calculator · feed cost calculator · FCR calculator · landed cost Pakistan feed additive
- **Manufacturer-facing:** feed additive distributor Pakistan · feed ingredient market Pakistan · animal nutrition distributor Pakistan · poultry feed market Pakistan

**Content types:**
- (A) evergreen technical guides;
- (B) commercial intelligence — Market Watch and supply alerts;
- (C) technical commentary by the panel.

**How the pieces work together:** a feed-mill nutritionist who searches "methionine cost calculator" finds the tool; searching "DL methionine vs MHA" finds the article; searching "methionine supplier Pakistan" finds the company. Authority is built before the sales call.

**Technical SEO:**
- one H1 per page;
- descriptive URLs as in §6;
- internal links between each article, its tool and its ingredient page;
- JSON-LD as in §5;
- author pages once team profiles exist;
- fast mobile pages.

**Measure monthly, from the CRM dashboard and optional server-side counters:**
- enquiries by type and source page;
- calculator runs and the "Discuss your result" rate;
- downloads;
- WhatsApp clicks;
- supplier applications.

---

# 10. Phased delivery

## Launch — the full live site (built per v5 §G1)
- **Pages:** Home · About · Company · Editorial policy · all Nutrition Services pages · Feed Mills · Request Sourcing · Ingredients directory and category pages · Methionine hub · Quality · For Suppliers · How we work · Supplier application · Contact · Ask a Nutritionist · Legal pages.
- **Tools:** Tools 1 and 2.
- **Knowledge:** Glossary and article 1, with articles 2–8 created as drafts in the CRM.
- **Gaps:** `CONTENT-GAPS.md` written and each gap seeded as a CRM task.

## Phase 1 — Build-out (the weeks after launch)
- Tools 3 and 4.
- Product pages as supply is secured; lysine, threonine and valine pages.
- Articles 2–8 as the panel reviews them.
- Team page, once profiles and consents exist.
- Pakistan Market Overview, once sourced figures are supplied.
- Simplified Chinese supplier page.
- Site search, FAQs, guides and downloads; the first Market Watch.

## Phase 2 — Authority (months 3–6)
- Amino Acid Value tool and Methionine Requirement Guide.
- Real case studies and trial summaries, only with written consent.
- Expanded guides; a regular Market Watch; the Market Watch email list.

## Phase 3 — Platform (6 months onward)
- **Customer portal:** quotations, order and shipment status, COA/SDS retrieval, invoices, sample and consultation requests.
- **Supplier portal:** market reports, customer-development pipeline, forecasts, trial results.
- Formulation Explorer.
- The portals are built as additional, industry-neutral modules of the Lite CRM package, so they can also be reused on other sites.

---

# 11. Owner inputs (the site works without them; each one fills a content gap)

- [ ] Logo approval, or acceptance of the wordmark in §4
- [ ] Pakistani partner's legal name, city and role; Singapore incorporation details when available
- [ ] Nutrition panel and team: bios, qualifications, photos and **signed consent** from each person
- [ ] Which products are `available`, `on request` or `information`, and which to publish on the website
- [ ] Written manufacturer permissions to use names or logos
- [ ] Nutrition panel approval of calculator defaults (§7.8)
- [ ] WhatsApp Business numbers
- [ ] Sourced aggregate figures for the Pakistan Market Overview
- [ ] Legal review of the legal pages
- [ ] Authors and reviewers for articles 2–8

---

# 12. Content deliverables expected from the builder

In addition to the technical deliverables in v5 §G:

1. Drafted copy for every launch page, following §0 (no public placeholders, no invented facts).
2. The Methionine hub and article 1 written in full, with sources listed.
3. A glossary of at least 20 terms, and FAQ drafts (Phase 1).
4. Outlines for articles 2–8, saved as CRM drafts.
5. `CONTENT-GAPS.md` listing every omission (page, what is missing, who supplies it), each also created as a CRM task.
6. Draft legal pages, marked internally for legal review.
7. SEO titles and meta descriptions for every launch page, entered in the CRM.

{{--
    Methionine hub (Content Blueprint v3 §7.5), published as AviNutra Technical Team content.
    No producer is named or criticised. Sources: only verified references (EFSA FEEDAP 2012, 2018).
--}}
@php
    use App\Support\SiteLinks;

    $calculator = SiteLinks::url('tools.methionine-value');
    $article = \App\Models\Article::query()->published()->where('slug', 'how-to-compare-methionine-sources')->first();
    $examples = [
        ['DL-Methionine 99%', '2.36', '1.00', '2.360'],
        ['MHA-FA 88% (liquid), about 75% equimolar', '1.76', '0.65', '2.708'],
        ['MHA-FA 88% (liquid), about 80% equimolar', '1.76', '0.70', '2.514'],
        ['MHA-FA 88% (liquid), 100% equimolar', '1.76', '0.88', '2.000'],
        ['L-Methionine 90%', '3.79', '0.909', '4.169'],
    ];
    $documents = [
        ['Certificate of analysis (COA)', 'Check that the batch number, date and results match the delivery and meet the agreed specification.'],
        ['Technical data sheet (TDS)', 'Check the typical specification and the date of the document.'],
        ['Safety data sheet (SDS)', 'Check that it is current and matches the product and its form.'],
        ['Product specification', 'The limits agreed in the contract: assay, moisture or loss on drying, heavy metals and arsenic.'],
        ['Registration documents', 'Check the product\'s registration status for feed use in the country of use.'],
        ['Halal certificate', 'Check the certificate number and validity with the issuing body.'],
        ['Certificate of origin', 'Check that it matches the shipment and the invoice.'],
        ['Quality certificates (FAMI-QS, GMP+, ISO)', 'Check the certificate number on the issuing body\'s public register, and that the scope covers the product and site.'],
    ];
@endphp
<x-layouts.site
    title="Methionine: Sources, Specification and Value — AviNutra"
    description="DL-Methionine, L-Methionine and methionine hydroxy analogue compared neutrally, how to calculate the cost per kg of effective methionine, and a documentation checklist."
>
    <x-page.hero
        eyebrow="Amino acids"
        title="Methionine: sources, specification and value"
        lead="A practical, neutral guide for feed manufacturers and nutritionists: what methionine does, how the sources differ, and how to compare them on value rather than price per tonne."
        :breadcrumbs="['Ingredients' => route('ingredients'), 'Amino Acids' => route('ingredients.category', 'amino-acids'), 'Methionine' => null]"
    >
        <p class="mt-5 text-sm text-muted">{{ \App\Models\Article::COMPANY_AUTHOR }} · Last updated 29 September 2026</p>
    </x-page.hero>

    <div class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[16rem_1fr] xl:px-8">
            <nav aria-label="On this page" class="hidden self-start lg:sticky lg:top-28 lg:block">
                <p class="eyebrow">On this page</p>
                <ol class="mt-4 space-y-2 text-sm">
                    @foreach (['what' => 'What is methionine?', 'sources' => 'Methionine sources', 'specification' => 'Why specification matters', 'dl-vs-l' => 'DL- vs L-Methionine', 'mha' => 'MHA vs DL-Methionine', 'prices' => 'Comparing prices properly', 'documents' => 'Documentation checklist', 'references' => 'Sources'] as $id => $label)
                        <li><a href="#{{ $id }}" class="text-green-700 hover:underline">{{ $label }}</a></li>
                    @endforeach
                </ol>
            </nav>

            <article class="prose-article max-w-3xl">
                <section id="what" aria-labelledby="h-what">
                    <h2 id="h-what">What is methionine?</h2>
                    <p>Methionine is an essential, sulphur-containing amino acid: birds cannot make it and must obtain it from their feed. It is used for protein synthesis and is also a source of methyl groups and of cysteine.</p>
                    <p>In most practical poultry diets based on cereals and soybean meal, methionine is the <strong>first-limiting amino acid</strong> — the one that runs short first, so it limits how well the other amino acids are used. Supplementary methionine is therefore added to almost all poultry feeds.</p>
                    <p>Requirements are usually expressed for <strong>methionine + cystine</strong> (the total sulphur amino acids) as well as for methionine alone, because birds can make cystine from methionine but not the other way round.</p>
                </section>

                <section id="sources" aria-labelledby="h-sources">
                    <h2 id="h-sources">Methionine sources</h2>
                    <ul>
                        <li><strong>DL-Methionine 99%</strong> — a powder containing equal amounts of the D- and L-forms. Birds convert the D-form into the L-form.</li>
                        <li><strong>L-Methionine</strong> — the L-form only, supplied at 99% and in lower-purity grades such as 90%.</li>
                        <li><strong>Methionine hydroxy analogue free acid (MHA-FA)</strong> — a liquid containing about 88% of the hydroxy analogue of methionine.</li>
                        <li><strong>Calcium salt of methionine hydroxy analogue (MHA-Ca)</strong> — a powder form of the hydroxy analogue.</li>
                    </ul>
                </section>

                <section id="specification" aria-labelledby="h-specification">
                    <h2 id="h-specification">Why specification matters</h2>
                    <p>Two products sold under similar names can differ in value. Before comparing prices, compare:</p>
                    <ul>
                        <li>purity or assay;</li>
                        <li>moisture or loss on drying;</li>
                        <li>heavy metals and arsenic;</li>
                        <li>physical form, and how it suits your plant;</li>
                        <li>stability, packaging and shelf life;</li>
                        <li>consistency from batch to batch;</li>
                        <li>the documentation that comes with each batch.</li>
                    </ul>
                </section>

                <section id="dl-vs-l" aria-labelledby="h-dl-vs-l">
                    <h2 id="h-dl-vs-l">DL-Methionine vs L-Methionine</h2>
                    <p>Poultry convert D-methionine into L-methionine efficiently, which is why DL-Methionine is used as the reference methionine source. At equal purity, the evidence does not robustly support a consistent premium for L-Methionine over DL-Methionine in poultry feed; where differences have been reported, they depend on the study, the age of the birds and the diet.</p>
                    <p>Purity, however, always matters. A 90% L-Methionine product supplies less methionine per kg than a 99% product, so its value per kg must be adjusted for its purity before its price is compared.</p>
                </section>

                <section id="mha" aria-labelledby="h-mha">
                    <h2 id="h-mha">MHA vs DL-Methionine</h2>
                    <p>The hydroxy analogue of methionine (MHA, also called HMTBa) is <strong>not methionine itself</strong>. It is a precursor that the bird converts into L-methionine. Its efficacy relative to DL-Methionine has been debated for many years.</p>
                    <p>The European Food Safety Authority's feed additives panel concluded that the hydroxy analogues show a somewhat lower bioefficacy than DL-Methionine in non-ruminant animals<sup><a href="#ref-1">1</a>, <a href="#ref-2">2</a></sup>. Its 2018 opinion cites a meta-analysis that reported relative biological efficiencies of 79% and 81% on an equimolar basis<sup><a href="#ref-2">2</a></sup>. MHA manufacturers take the position that the hydroxy analogue is equivalent to DL-Methionine on an equimolar basis.</p>
                    <p>Two points help when reading these figures:</p>
                    <ul>
                        <li><strong>Equimolar basis</strong> compares equal molecular amounts of the active substances. <strong>Product basis</strong> compares equal weights of the products as sold. Because MHA-FA contains about 88% of the active substance, a value on a product basis is lower than the same value on an equimolar basis.</li>
                        <li>The assumption you choose changes the result. In any comparison, state the value factor you used and where it comes from, and confirm it with the manufacturer's documentation and your nutritionist.</li>
                    </ul>
                    <p>We express the value factor on a <strong>product basis</strong> — the kg of DL-Methionine 99% replaced by 1 kg of MHA-FA 88% as sold — and show the equimolar efficacy each factor assumes:</p>
                    <div class="table-wrap">
                        <table>
                            <caption>MHA-FA 88%: value factors and the equimolar efficacy they assume (indicative, pending nutrition-panel approval)</caption>
                            <thead><tr><th scope="col">Value factor (product basis)</th><th scope="col">Equimolar efficacy assumed</th><th scope="col">Position</th></tr></thead>
                            <tbody>
                                <tr><td>0.65</td><td>about 75%</td><td>A more conservative assumption</td></tr>
                                <tr><td>0.70</td><td>about 80%</td><td>The meta-analysis cited by EFSA (79–81% equimolar)<sup><a href="#ref-2">2</a></sup></td></tr>
                                <tr><td>0.88</td><td>100%</td><td>The MHA manufacturers' position</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="formula">Value factor ≈ active content (0.88) × equimolar efficacy × (149.2 ÷ 150.2)</p>
                    <p>The last term converts from the hydroxy analogue (molar mass about 150.2 g/mol) to methionine (about 149.2 g/mol). For example, 0.88 × 0.80 × 0.993 ≈ 0.70, and 0.88 × 0.75 × 0.993 ≈ 0.66, rounded to 0.65. The manufacturers' figure of 0.88 counts 1 kg of the active substance as equal to 1 kg of DL-Methionine; with the molar-mass correction, 100% equimolar would be about 0.87.</p>
                </section>

                <section id="prices" aria-labelledby="h-prices">
                    <h2 id="h-prices">How to compare prices properly</h2>
                    <p>Price per tonne does not tell you what you are paying for the methionine. Compare sources on the <strong>cost per kg of effective methionine</strong> instead:</p>
                    <p class="formula">Cost per kg of effective methionine = price per kg ÷ value factor</p>
                    <p>The <strong>value factor</strong> is the kg of DL-Methionine 99% replaced by 1 kg of the product (DL-Methionine 99% = 1.00).</p>
                    <div class="table-wrap">
                        <table>
                            <caption>Worked example with illustrative prices (not market prices)</caption>
                            <thead>
                                <tr><th scope="col">Product</th><th scope="col">Price (USD/kg)</th><th scope="col">Value factor</th><th scope="col">Cost per kg of effective methionine (USD)</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($examples as [$name, $price, $factor, $result])
                                    <tr><td>{{ $name }}</td><td>{{ $price }}</td><td>{{ $factor }}</td><td><strong>{{ $result }}</strong></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p>The same MHA-FA price gives a cost of effective methionine between 2.000 and 2.708 depending on the value factor assumed — a difference larger than many price negotiations. That is why the assumption must be explicit.</p>
                    @if ($calculator)
                        <p><a href="{{ $calculator }}" class="btn-primary not-prose">Try the Methionine Value Calculator</a></p>
                    @endif
                    @if ($article)
                        <p>Read more: <a href="{{ route('knowledge.show', $article->slug) }}">{{ $article->title }}</a>.</p>
                    @endif
                </section>

                <section id="documents" aria-labelledby="h-documents">
                    <h2 id="h-documents">Documentation checklist</h2>
                    <p>Ask for these documents, and check them before you accept a product or a new supplier:</p>
                    <dl class="checklist">
                        @foreach ($documents as [$document, $check])
                            <div><dt>{{ $document }}</dt><dd>{{ $check }}</dd></div>
                        @endforeach
                    </dl>
                </section>

                <section id="references" aria-labelledby="h-references">
                    <h2 id="h-references">Sources</h2>
                    <ol class="references">
                        <li id="ref-1">EFSA FEEDAP Panel (2012). Scientific Opinion on DL-methionine, DL-methionine sodium salt, the hydroxy analogue of methionine and the calcium salt of methionine hydroxy analogue in all animal species… <em>EFSA Journal</em> 10(3):2623. <a href="https://doi.org/10.2903/j.efsa.2012.2623" rel="noopener">doi:10.2903/j.efsa.2012.2623</a></li>
                        <li id="ref-2">EFSA FEEDAP Panel (2018). Safety and efficacy of hydroxy analogue of methionine and its calcium salt for all animal species (title shortened; product name omitted). <em>EFSA Journal</em> 16(3):5198. <a href="https://doi.org/10.2903/j.efsa.2018.5198" rel="noopener">doi:10.2903/j.efsa.2018.5198</a></li>
                    </ol>
                </section>
            </article>
        </div>
    </div>

    <x-page.cta-band title="Comparing methionine sources for your feed?" text="Send us your prices and formulation basis, and our nutrition team will help you compare them on value." label="Ask a Nutritionist" :parameters="['topic' => 'methionine']" />
</x-layouts.site>

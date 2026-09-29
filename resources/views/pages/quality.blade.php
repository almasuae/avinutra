{{-- Quality (v3 §7.6): our working method and principles, not claims of audits already carried out. --}}
@php
    $steps = [
        ['heroicon-o-shield-check', 'Supplier qualification and due diligence', 'Before we work with a manufacturer, we review its documentation, quality systems and supply record.'],
        ['heroicon-o-document-magnifying-glass', 'Certificate verification', 'We check quality and registration certificates against the issuing body\'s public register where one exists.'],
        ['heroicon-o-clipboard-document-list', 'Product specification control', 'Each product is supplied against a written specification agreed before the first order.'],
        ['heroicon-o-document-check', 'COA verification for each batch', 'The certificate of analysis for every batch is checked against the agreed specification.'],
        ['heroicon-o-archive-box', 'Batch traceability and retention samples', 'Batches are traceable from the manufacturer to delivery, and retention samples are kept where appropriate.'],
        ['heroicon-o-beaker', 'Independent laboratory testing', 'Where appropriate, samples are tested by an independent laboratory.'],
        ['heroicon-o-tag', 'Packaging and label inspection', 'Packaging and labels are checked for integrity and for the information required.'],
        ['heroicon-o-truck', 'Shipping documentation and storage conditions', 'Shipping documents are complete, and storage conditions suit the product.'],
        ['heroicon-o-chat-bubble-left-right', 'Complaint handling and product recall', 'Complaints are recorded and investigated, and a recall procedure is in place.'],
    ];
@endphp
<x-layouts.site
    title="Quality — AviNutra"
    description="Quality is part of the product: supplier qualification, certificate verification, specification control, COA verification and traceability."
>
    <x-page.hero
        eyebrow="Quality"
        title="Quality Is Part of the Product."
        lead="A feed ingredient is only as good as its specification, its documentation and the consistency of every batch. Our quality process is built into how we select, supply and support products."
        :breadcrumbs="['Quality' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.heading eyebrow="Our working method" title="Our quality process includes…" />
            <ol class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($steps as [$icon, $title, $text])
                    <li class="card">
                        <span class="flex size-14 items-center justify-center rounded-full border-2 border-green-700 text-green-700">
                            <x-dynamic-component :component="$icon" class="size-7" aria-hidden="true" />
                        </span>
                        <h3 class="mt-5 text-xl">{{ $title }}</h3>
                        <p class="mt-2 text-base text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
            <x-page.heading title="Documents on request" />
            <p class="mt-6 text-lg">Technical documents — TDS, SDS, certificates of analysis, specifications, halal, ISO, FAMI-QS or GMP+ certificates, certificates of origin, regulatory documents, labels and packaging details — are sent on request. They are never published openly.</p>
            @if ($href = \App\Support\SiteLinks::url('contact', ['type' => 'document']))
                <a href="{{ $href }}" class="btn-secondary mt-6">Request documents</a>
            @endif
        </div>
    </section>

    <x-page.cta-band />
</x-layouts.site>

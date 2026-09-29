{{-- Company (v3 §3, §7.2): status statement, Pakistan partner, contracting entity, disclosure. All from Site settings. --}}
@php
    $site = app(\App\Settings\SiteSettings::class);
    $entity = \App\Providers\AppServiceProvider::contractingEntity();
@endphp
<x-layouts.site
    title="Company — AviNutra"
    description="AviNutra's legal status, partner in Pakistan, contracting entity and commercial-relationship disclosure."
>
    <x-page.hero
        eyebrow="About us"
        title="Company"
        lead="How AviNutra is set up, who contracts with you, and how we handle commercial relationships."
        :breadcrumbs="['About' => route('about'), 'Company' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-4xl space-y-12 px-4 py-16 sm:px-6">
            <div>
                <x-page.heading title="Legal status" />
                <p class="mt-6 text-lg">{{ $site->statusStatement() }}</p>
                @if ($site->sg_incorporated && filled($site->registered_office))
                    <p class="mt-4 text-lg"><strong>Registered office:</strong> {{ $site->registered_office }}</p>
                @endif
            </div>

            @if (filled($site->pk_partner_name))
                <div>
                    <x-page.heading title="Our partner in Pakistan" />
                    <p class="mt-6 text-lg">
                        {{ $site->pk_partner_name }}@if (filled($site->pk_partner_city)), {{ $site->pk_partner_city }}@endif@if (filled($site->pk_partner_role)) — {{ $site->pk_partner_role }}@endif.
                    </p>
                </div>
            @endif

            <div>
                <x-page.heading title="Who contracts with you" />
                @if ($entity)
                    <p class="mt-6 text-lg">Quotations and contracts are issued by <strong>{{ $entity }}</strong>. Every quotation states the contracting entity.</p>
                @else
                    <p class="mt-6 text-lg">Every quotation states which legal entity is contracting, before you commit to anything.</p>
                @endif
            </div>

            <div class="rounded-(--radius-card) border-l-4 border-orange-500 bg-surface p-7">
                <h2 class="text-2xl">Commercial-relationship disclosure</h2>
                <p class="mt-4 text-lg italic">
                    “We have commercial relationships with some of the manufacturers whose products we supply. Our technical evaluations state the basis of any comparison, and we will tell you when we are recommending a product we sell.”
                </p>
            </div>
        </div>
    </section>

    <x-page.cta-band title="Questions about working with us?" text="Contact us about our status, terms or the documents you need." label="Contact us" route="contact" />
</x-layouts.site>

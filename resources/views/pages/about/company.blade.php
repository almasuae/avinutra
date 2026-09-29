{{--
    Company (v3 §7.2, as amended 29 Sep 2026): no company-status or country details on
    the public site. Those Site settings drive quotations in the CRM only.
--}}
<x-layouts.site
    title="Company — AviNutra"
    description="Who contracts with you when you buy from AviNutra, and how we handle commercial relationships with manufacturers."
>
    <x-page.hero
        eyebrow="About us"
        title="Company"
        lead="Who contracts with you, and how we handle commercial relationships with manufacturers."
        :breadcrumbs="['About' => route('about'), 'Company' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-4xl space-y-12 px-4 py-16 sm:px-6">
            <div>
                <x-page.heading title="Who contracts with you" />
                <p class="mt-6 text-lg">Every quotation states the contracting legal entity, before you commit to anything.</p>
            </div>

            <div class="rounded-(--radius-card) border-l-4 border-orange-500 bg-surface p-7">
                <h2 class="text-2xl">Commercial-relationship disclosure</h2>
                <p class="mt-4 text-lg italic">
                    “We have commercial relationships with some of the manufacturers whose products we supply. Our technical evaluations state the basis of any comparison, and we will tell you when we are recommending a product we sell.”
                </p>
            </div>
        </div>
    </section>

    <x-page.cta-band title="Questions about working with us?" text="Contact us about our terms or the documents you need." label="Contact us" route="contact" />
</x-layouts.site>

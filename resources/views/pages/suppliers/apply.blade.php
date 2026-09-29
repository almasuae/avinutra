{{-- Supplier Particulars (v3 §7.7, §8): suppliers introduce their company; uploads kept confidential, NDA on request. --}}
<x-layouts.site
    title="Supplier Particulars — AviNutra"
    description="Introduce your company to AviNutra: share your products, capacity, certifications and target markets."
>
    <x-page.hero
        eyebrow="For suppliers"
        title="Become a Supply Partner"
        lead="Introduce your company: share your particulars, your products and the markets you want to develop. Our team reviews every submission."
        :breadcrumbs="['For Suppliers' => route('suppliers'), 'Supplier Particulars' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.7fr_1fr] xl:px-8">
            <div class="card">
                <h2 class="text-2xl">Supplier Particulars</h2>
                <p class="mt-2 text-base text-muted">Fields marked * are required. You can attach your catalogue, TDS, SDS, COA, certificates and company profile.</p>
                <div class="mt-6"><x-site.enquiry-form form="supplier_application" /></div>
            </div>
            <aside class="space-y-6 self-start">
                <div class="rounded-(--radius-card) border-l-4 border-orange-500 bg-surface p-7">
                    <h2 class="text-xl">Confidentiality</h2>
                    <p class="mt-3 text-base">Documents you upload are kept confidential, stored privately and used only to evaluate your company and products. We are happy to sign an NDA on request.</p>
                </div>
                <div class="rounded-(--radius-card) bg-surface p-7">
                    <h2 class="text-xl">What happens next</h2>
                    <ol class="mt-4 list-decimal space-y-2 pl-5 text-base">
                        <li>We confirm receipt by e-mail.</li>
                        <li>Our team reviews your products and documents.</li>
                        <li>We contact you to discuss the next steps.</li>
                    </ol>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>

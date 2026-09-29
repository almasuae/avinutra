{{-- Request Sourcing Support form (v3 §7.3, §8). --}}
<x-layouts.site
    title="Request Sourcing Support — AviNutra"
    description="Tell us the feed ingredient and specification you need. We look for reliable international sources."
>
    <x-page.hero
        eyebrow="Nutrition Services"
        title="Request Sourcing Support"
        lead="Need a feed ingredient that is not in our directory, or a second source for one you use? Tell us the product, specification and quantity, and we will look for reliable international sources."
        :breadcrumbs="['Nutrition Services' => route('services'), 'Request Sourcing' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.7fr_1fr] xl:px-8">
            <div class="card">
                <h2 class="text-2xl">Your requirement</h2>
                <p class="mt-2 text-base text-muted">Fields marked * are required.</p>
                <div class="mt-6"><x-site.enquiry-form form="sourcing_request" /></div>
            </div>
            <aside class="space-y-6 self-start">
                <div class="rounded-(--radius-card) bg-surface p-7">
                    <h2 class="text-xl">What happens next</h2>
                    <ol class="mt-4 list-decimal space-y-2 pl-5 text-base text-ink">
                        <li>We confirm receipt by e-mail.</li>
                        <li>A member of our team reviews your requirement.</li>
                        <li>We come back with questions or suitable options.</li>
                    </ol>
                </div>
                <div class="rounded-(--radius-card) bg-surface p-7 text-base text-muted">
                    Your requirement is used only to answer your enquiry. See our <a href="{{ route('legal.privacy') }}" class="font-semibold text-green-700 underline">Privacy Policy</a>.
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>

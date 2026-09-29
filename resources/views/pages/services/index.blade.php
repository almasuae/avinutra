{{-- Nutrition Services overview (v3 §7.3). --}}
<x-layouts.site
    title="Nutrition Services — AviNutra"
    description="Formulation support, ingredient evaluation, product substitution, feed economics, supplier qualification and technical trials for poultry feed manufacturers."
>
    <x-page.hero
        eyebrow="Nutrition Services"
        title="Nutrition expertise for poultry feed manufacturers"
        lead="Technical knowledge before commercial recommendation. We help feed mills make better formulation, ingredient and purchasing decisions."
        :breadcrumbs="['Nutrition Services' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <x-page.heading eyebrow="Our services" title="How we can help" />
            <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $slug => $service)
                    <li>
                        <a href="{{ route('services.show', $slug) }}" class="card group flex h-full flex-col transition hover:-translate-y-0.5 hover:border-green-700">
                            <span class="flex size-14 items-center justify-center rounded-full border-2 border-green-700 text-green-700">
                                <x-dynamic-component :component="$service['icon']" class="size-7" aria-hidden="true" />
                            </span>
                            <span class="mt-5 font-heading text-xl font-black text-green-900">{{ $service['title'] }}</span>
                            <span class="mt-2 flex-1 text-base text-muted">{{ $service['summary'] }}</span>
                            <span class="mt-5 font-semibold text-green-700 group-hover:underline">Read more →</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="bg-surface">
        <div class="mx-auto grid max-w-[84rem] gap-6 px-4 py-16 sm:px-6 md:grid-cols-2 xl:px-8">
            <div class="card">
                <p class="eyebrow">For feed manufacturers</p>
                <h2 class="mt-2 text-2xl">Solutions for Feed Manufacturers</h2>
                <p class="mt-3 text-base text-muted">From your requirement to technical follow-up after supply: how we work with a feed mill.</p>
                <a href="{{ route('services.feed-mills') }}" class="btn-secondary mt-6">See the customer journey</a>
            </div>
            <div class="card">
                <p class="eyebrow">Not in our directory?</p>
                <h2 class="mt-2 text-2xl">Request Sourcing Support</h2>
                <p class="mt-3 text-base text-muted">Tell us the product and specification you need, and we will look for reliable international sources.</p>
                <a href="{{ route('services.request-sourcing') }}" class="btn-secondary mt-6">Request sourcing support</a>
            </div>
        </div>
    </section>

    <x-page.cta-band title="Discuss your formulation or sourcing question" label="Talk to a Nutritionist" />
</x-layouts.site>

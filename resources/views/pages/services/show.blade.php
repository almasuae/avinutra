{{-- One Nutrition Services page (v3 §7.3); ends with "Discuss this with a nutritionist". --}}
<x-layouts.site :title="$service['title'].' — Nutrition Services — AviNutra'" :description="$service['summary']">
    <x-page.hero
        eyebrow="Nutrition Services"
        :title="$service['title']"
        :lead="$service['lead']"
        :breadcrumbs="['Nutrition Services' => route('services'), $service['title'] => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.6fr_1fr] xl:px-8">
            <div class="space-y-12">
                @isset($service['flow'])
                    <div>
                        <x-page.heading :title="$service['flow_label'] ?? 'Our approach'" />
                        <x-page.flow class="mt-8" :label="$service['flow_label'] ?? null" :steps="$service['flow']" />
                    </div>
                @endisset

                @foreach ($service['sections'] as $section)
                    <div>
                        <x-page.heading :title="$section['heading']" />
                        @isset($section['text'])
                            <p class="mt-6 text-lg">{{ $section['text'] }}</p>
                        @endisset
                        @isset($section['items'])
                            <ul class="mt-6 space-y-3">
                                @foreach ($section['items'] as $item)
                                    <li class="flex gap-3 text-lg">
                                        <x-heroicon-o-check-circle class="mt-1 size-6 shrink-0 text-green-700" aria-hidden="true" />
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endisset
                    </div>
                @endforeach
            </div>

            <aside aria-label="Other services" class="self-start rounded-(--radius-card) bg-surface p-7">
                <h2 class="text-xl">Other services</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($others as $otherSlug => $other)
                        <li><a href="{{ route('services.show', $otherSlug) }}" class="font-semibold text-green-700 hover:underline">{{ $other['title'] }}</a></li>
                    @endforeach
                    <li><a href="{{ route('services.feed-mills') }}" class="font-semibold text-green-700 hover:underline">Solutions for Feed Manufacturers</a></li>
                </ul>
            </aside>
        </div>
    </section>

    <x-page.cta-band
        title="Discuss this with a nutritionist"
        text="Send us your question and, if you wish, your formulation or certificate of analysis. We reply with technical and commercial input."
        label="Talk to a Nutritionist"
        :parameters="['topic' => $slug]"
    />
</x-layouts.site>

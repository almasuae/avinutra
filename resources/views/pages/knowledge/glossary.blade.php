{{-- Glossary (v3 §7.9): every technical term used on the site. --}}
<x-layouts.site
    title="Glossary — Knowledge Centre — AviNutra"
    description="Feed and poultry-nutrition terms explained: amino acids, digestibility, feed conversion, certificates and more."
>
    <x-page.hero
        eyebrow="Knowledge Centre"
        title="Glossary"
        lead="Technical terms used on this site, explained in plain English."
        :breadcrumbs="['Insights' => route('knowledge'), 'Glossary' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
            <nav aria-label="Letters" class="mb-10">
                <ul class="flex flex-wrap gap-2">
                    @foreach ($groups->keys() as $letter)
                        <li><a href="#letter-{{ $letter }}" class="flex size-10 items-center justify-center rounded-full border-2 border-line font-bold text-green-900 hover:border-green-700">{{ $letter }}</a></li>
                    @endforeach
                </ul>
            </nav>
            @foreach ($groups as $letter => $terms)
                <section id="letter-{{ $letter }}" aria-labelledby="heading-{{ $letter }}" class="mb-10 scroll-mt-28">
                    <h2 id="heading-{{ $letter }}" class="text-3xl text-orange-text">{{ $letter }}</h2>
                    <dl class="mt-4 divide-y divide-line">
                        @foreach ($terms as $term)
                            <div id="{{ $term->slug }}" class="scroll-mt-28 py-5">
                                <dt class="font-heading text-xl font-black text-green-900">{{ $term->term }}</dt>
                                <dd class="mt-2 text-base text-ink">{{ $term->definition }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    </section>
</x-layouts.site>

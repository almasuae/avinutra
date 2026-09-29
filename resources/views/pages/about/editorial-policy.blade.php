{{-- Editorial policy (v3 §7.2). --}}
<x-layouts.site
    title="Editorial Policy — AviNutra"
    description="Who writes and reviews AviNutra's technical content, how sources are cited, how often content is reviewed and how corrections are handled."
>
    <x-page.hero
        eyebrow="About us"
        title="Editorial Policy"
        lead="Our technical content is written to help feed professionals make better decisions. This is how we make sure it can be trusted."
        :breadcrumbs="['About' => route('about'), 'Editorial Policy' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-4xl space-y-12 px-4 py-16 sm:px-6">
            <div>
                <x-page.heading title="Who writes and reviews our content" />
                <p class="mt-6 text-lg">Technical articles published under a person's name are written or reviewed by that person, and are published only after they have done so. Each article shows <strong>Author · Reviewed by · Last reviewed</strong>.</p>
                <p class="mt-4 text-lg">General technical pages are company content. They show <strong>{{ \App\Models\Article::COMPANY_AUTHOR }}</strong> and the date they were last updated.</p>
            </div>
            <div>
                <x-page.heading title="How we cite sources" />
                <p class="mt-6 text-lg">We cite the sources behind technical statements, with a link where one is available. Every market figure, price trend or supply event we publish shows its source and date. We do not publish information about individual transactions or named importers.</p>
            </div>
            <div>
                <x-page.heading title="How often content is reviewed" />
                <p class="mt-6 text-lg">We review technical content regularly and whenever new evidence, regulations or product information make it necessary. The last-reviewed date on each article tells you when it was last checked.</p>
            </div>
            <div>
                <x-page.heading title="Corrections" />
                <p class="mt-6 text-lg">If you find an error, please tell us through the <a href="{{ route('contact') }}" class="font-semibold text-green-700 underline">Contact page</a>. We correct factual errors promptly and update the article's last-reviewed date.</p>
            </div>
            <div>
                <x-page.heading title="Nutritional language" />
                <p class="mt-6 text-lg">We describe feed ingredients in nutritional terms. We do not make therapeutic or disease claims.</p>
            </div>
            <div class="rounded-(--radius-card) border-l-4 border-orange-500 bg-surface p-7">
                <h2 class="text-2xl">Commercial-relationship disclosure</h2>
                <p class="mt-4 text-lg italic">
                    “We have commercial relationships with some of the manufacturers whose products we supply. Our technical evaluations state the basis of any comparison, and we will tell you when we are recommending a product we sell.”
                </p>
            </div>
        </div>
    </section>
</x-layouts.site>

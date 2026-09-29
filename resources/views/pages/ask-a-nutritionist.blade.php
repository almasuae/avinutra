{{-- Ask a Nutritionist (v3 §8): routed to the nutrition mailbox; topic pre-selected from ?topic=. --}}
<x-layouts.site
    title="Ask a Nutritionist — AviNutra"
    description="Send a formulation, ingredient or feed-economics question to AviNutra's nutrition team."
>
    <x-page.hero
        eyebrow="Ask a Nutritionist"
        title="Ask a Nutritionist"
        lead="Have a formulation, ingredient or feed-economics question? Send it to our nutrition team. You can attach your formulation or a certificate of analysis."
        :breadcrumbs="['Ask a Nutritionist' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.7fr_1fr] xl:px-8">
            <div class="card">
                <h2 class="text-2xl">Your question</h2>
                <p class="mt-2 text-base text-muted">Fields marked * are required.</p>
                <div class="mt-6"><x-site.enquiry-form form="ask_nutritionist" :values="$values" /></div>
            </div>
            <aside class="space-y-6 self-start">
                <div class="rounded-(--radius-card) bg-surface p-7">
                    <h2 class="text-xl">Good questions include</h2>
                    <ul class="mt-4 list-disc space-y-2 pl-5 text-base">
                        <li>Can product A replace product B in my broiler diets?</li>
                        <li>Which methionine source gives me the lowest cost per kg of effective methionine?</li>
                        <li>How should I read this certificate of analysis?</li>
                    </ul>
                </div>
                <div class="rounded-(--radius-card) bg-surface p-7 text-base text-muted">
                    Files you attach are stored privately and used only to answer your question.
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>

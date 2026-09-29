{{-- Team (v3 §7.2): only profiles that are published with consent on file. --}}
<x-layouts.site
    title="Our Team — AviNutra"
    description="AviNutra's nutrition advisers, consultants and team members."
>
    <x-page.hero
        eyebrow="About us"
        title="Our Team"
        lead="The people behind our technical recommendations. Advisers, consultants and employees are shown as such."
        :breadcrumbs="['About' => route('about'), 'Team' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto max-w-[84rem] px-4 py-16 sm:px-6 xl:px-8">
            <ul class="grid gap-8 md:grid-cols-2">
                @foreach ($profiles as $profile)
                    <li id="{{ $profile->slug }}" class="card flex scroll-mt-28 flex-col gap-6 sm:flex-row">
                        @if ($profile->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile->photo_path) }}" alt="{{ $profile->name }}" width="128" height="128" loading="lazy" class="size-32 shrink-0 rounded-full object-cover">
                        @endif
                        <div>
                            <h2 class="text-2xl">{{ $profile->name }}</h2>
                            <p class="mt-1 font-semibold text-orange-text">{{ $profile->role_type->getLabel() }}@if ($profile->job_title) · {{ $profile->job_title }}@endif</p>
                            @if ($profile->qualification)
                                <p class="mt-3 text-base">{{ $profile->qualification }}@if ($profile->university), {{ $profile->university }}@endif @if ($profile->qualification_year)({{ $profile->qualification_year }})@endif</p>
                            @endif
                            @if ($profile->experience_years)
                                <p class="mt-1 text-base text-muted">{{ $profile->experience_years }} years in poultry and feed</p>
                            @endif
                            @if ($profile->specialisations)
                                <p class="mt-3 text-base"><strong>Specialisations:</strong> {{ $profile->specialisations }}</p>
                            @endif
                            @if ($profile->languages)
                                <p class="mt-1 text-base"><strong>Languages:</strong> {{ $profile->languages }}</p>
                            @endif
                            @if ($profile->bio)
                                <p class="mt-3 text-base text-muted">{{ $profile->bio }}</p>
                            @endif
                            @if (! empty($profile->publications))
                                <ul class="mt-3 list-disc space-y-1 pl-5 text-base">
                                    @foreach ($profile->publications as $publication)
                                        <li>@if (! empty($publication['url']))<a href="{{ $publication['url'] }}" class="text-green-700 underline" target="_blank" rel="noopener noreferrer">{{ $publication['title'] ?? $publication['url'] }}</a>@else{{ $publication['title'] ?? '' }}@endif</li>
                                    @endforeach
                                </ul>
                            @endif
                            @foreach (['Articles written' => $profile->authoredArticles, 'Articles reviewed' => $profile->reviewedArticles] as $heading => $articles)
                                @if ($articles->isNotEmpty())
                                    <p class="mt-4 text-sm font-semibold text-green-900">{{ $heading }}</p>
                                    <ul class="mt-1 space-y-1 text-base">
                                        @foreach ($articles as $article)
                                            <li><a href="{{ route('knowledge.show', $article->slug) }}" class="text-green-700 underline">{{ $article->title }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts.site>

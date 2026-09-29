@props(['title', 'description', 'updated' => '29 September 2026'])
{{-- Layout for the legal pages: sensible drafts pending legal review (CONTENT-GAPS #9). --}}
<x-layouts.site :title="$title.' — AviNutra'" :description="$description">
    <x-page.hero eyebrow="Legal" :title="$title" :breadcrumbs="['Legal' => null, $title => null]">
        <p class="mt-4 text-sm text-muted">Last updated {{ $updated }}</p>
    </x-page.hero>
    <div class="bg-white">
        <div class="prose-article mx-auto max-w-3xl px-4 py-14 sm:px-6">
            {{ $slot }}
        </div>
    </div>
</x-layouts.site>

{{-- Local development only (see routes/web.php). --}}
<x-layouts.site title="Enquiry form test (local only)">
    @push('head')
        <meta name="robots" content="noindex, nofollow">
    @endpush

    <section class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        <p class="text-sm font-medium uppercase tracking-wide text-accent">Local development only</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Enquiry form test</h1>
        <p class="mt-3 text-muted">
            Submissions go to the local CRM inbox at <a class="underline" href="{{ url('/crm/enquiries') }}">/crm/enquiries</a>.
            E-mails are written to <code>storage/logs/laravel.log</code>.
        </p>

        <div class="mt-8">
            <livewire:lite-crm.enquiry-form
                type="general"
                :fields="[
                    'name' => ['required' => true],
                    'email' => ['required' => true],
                    'company',
                    'city',
                    'country',
                    'message' => ['required' => true],
                ]"
                :uploads="2"
            />
        </div>
    </section>
</x-layouts.site>

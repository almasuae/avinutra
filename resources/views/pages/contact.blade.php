{{-- Contact (v3 §7.10): offices from Site settings, contracting entity, enquiry-type picker, WhatsApp once set. --}}
@php
    use App\Support\SiteLinks;

    $site = app(\App\Settings\SiteSettings::class);
    $entity = \App\Providers\AppServiceProvider::contractingEntity();
    $whatsappSales = SiteLinks::whatsapp($site->whatsapp_sales);
    $whatsappNutrition = SiteLinks::whatsapp($site->whatsapp_nutrition);
    $mailboxes = array_filter([
        'General enquiries' => $site->emails['info'] ?? null,
        'Sales, quotations and sourcing' => $site->emails['sales'] ?? null,
        'Technical and nutrition' => $site->emails['nutrition'] ?? null,
        'Supplier partnerships' => $site->emails['partners'] ?? null,
    ]);
@endphp
<x-layouts.site
    title="Contact — AviNutra"
    description="Contact AviNutra about nutrition consulting, feed ingredients, quotations, samples, documents or supplier partnerships."
>
    <x-page.hero
        eyebrow="Contact"
        title="Contact us"
        lead="Choose the type of enquiry below, so it reaches the right team straight away."
        :breadcrumbs="['Contact' => null]"
    />

    <section class="bg-white">
        <div class="mx-auto grid max-w-[84rem] gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.7fr_1fr] xl:px-8">
            <div>
                <h2 class="text-2xl">What is your enquiry about?</h2>
                <nav aria-label="Enquiry type" class="mt-5">
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($types as $key => $option)
                            <li>
                                <a href="{{ route('contact', ['type' => $key]) }}#enquiry"
                                   @if ($key === $type) aria-current="true" @endif
                                   @class(['inline-block rounded-full border-2 px-4 py-2 text-sm font-semibold transition', 'border-green-800 bg-green-800 text-white' => $key === $type, 'border-line bg-white text-green-900 hover:border-green-700' => $key !== $type])>
                                    {{ $option['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <div id="enquiry" class="card mt-8 scroll-mt-28">
                    <h2 class="text-2xl">{{ $types[$type]['label'] }}</h2>
                    <p class="mt-1 text-base text-muted">{{ $types[$type]['hint'] }} Fields marked * are required.</p>
                    <div class="mt-6">
                        <x-site.enquiry-form :form="$type" :values="$values" :key="'contact-'.$type" />
                    </div>
                </div>
            </div>

            <aside class="space-y-6 self-start">
                <div class="rounded-(--radius-card) bg-surface p-7">
                    <h2 class="text-xl">Where we are</h2>
                    <dl class="mt-4 space-y-4 text-base">
                        <div>
                            <dt class="font-semibold text-green-900">Singapore</dt>
                            <dd>
                                @if ($site->sg_incorporated && filled($site->registered_office))
                                    {{ $site->registered_office }}
                                @else
                                    Singapore — office being established
                                @endif
                            </dd>
                        </div>
                        @if (filled($site->pk_partner_name))
                            <div>
                                <dt class="font-semibold text-green-900">Pakistan</dt>
                                <dd>{{ $site->pk_partner_name }}@if (filled($site->pk_partner_city)), {{ $site->pk_partner_city }}@endif@if (filled($site->pk_partner_role)) — {{ $site->pk_partner_role }}@endif</dd>
                            </div>
                        @endif
                    </dl>
                    <p class="mt-5 text-sm text-muted">{{ $site->statusStatement() }}</p>
                    <p class="mt-3 text-sm text-muted">
                        @if ($entity)
                            Contracting entity: <strong class="text-ink">{{ $entity }}</strong>. Every quotation states the contracting entity.
                        @else
                            Every quotation states which legal entity is contracting.
                        @endif
                    </p>
                </div>

                <div class="rounded-(--radius-card) bg-surface p-7">
                    <h2 class="text-xl">E-mail</h2>
                    <dl class="mt-4 space-y-3 text-base">
                        @foreach ($mailboxes as $label => $email)
                            <div>
                                <dt class="text-sm text-muted">{{ $label }}</dt>
                                <dd><a href="mailto:{{ $email }}" class="font-semibold text-green-700 hover:underline">{{ $email }}</a></dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if ($whatsappSales || $whatsappNutrition)
                    <div class="rounded-(--radius-card) bg-surface p-7">
                        <h2 class="text-xl">WhatsApp</h2>
                        <div class="mt-4 flex flex-col gap-3">
                            @if ($whatsappSales)
                                <a href="{{ $whatsappSales }}" class="btn-cta">WhatsApp Sales</a>
                            @endif
                            @if ($whatsappNutrition)
                                <a href="{{ $whatsappNutrition }}" class="btn-cta">WhatsApp Nutrition Team</a>
                            @endif
                            <a href="{{ route('contact', ['type' => 'call']) }}#enquiry" class="btn-secondary">Request a Call</a>
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.site>

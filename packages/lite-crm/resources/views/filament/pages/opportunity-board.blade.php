<x-filament-panels::page>
    {{-- Filament's stylesheet has no utility classes for custom layouts, so the board uses inline styles. --}}
    <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
        <label for="board-pipeline" style="font-weight:500;">{{ __('lite-crm::opportunities.fields.pipeline') }}</label>
        <select id="board-pipeline" wire:model.live="pipeline"
            style="min-width:14rem; border:1px solid rgb(209 213 219); border-radius:0.5rem; padding:0.4rem 2rem 0.4rem 0.6rem; background-color:transparent;">
            @foreach ($this->getPipelineOptions() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        <span style="font-size:0.85rem; opacity:0.7;">{{ __('lite-crm::opportunities.board.help', ['days' => \LiteCrm\Filament\Pages\OpportunityBoard::CLOSED_DAYS]) }}</span>
    </div>

    @if (! $this->hasPipelines())
        <x-filament::section>{{ __('lite-crm::opportunities.board.no_pipelines') }}</x-filament::section>
    @else
        @php($columns = $this->getColumns())
        <div style="display:flex; gap:1rem; overflow-x:auto; padding-bottom:1rem; align-items:flex-start;" role="list" aria-label="{{ __('lite-crm::opportunities.board.title') }}">
            @foreach ($columns as $column)
                @php($stage = $column['stage'])
                <section
                    role="listitem"
                    aria-label="{{ $stage->name }}"
                    wire:key="stage-{{ $stage->getKey() }}"
                    x-data="{ over: false }"
                    x-on:dragover.prevent="over = true"
                    x-on:dragleave="over = false"
                    x-on:drop.prevent="over = false; $wire.moveOpportunity(Number($event.dataTransfer.getData('text/plain')), {{ $stage->getKey() }})"
                    x-bind:style="over ? 'outline:2px dashed rgb(59 130 246); outline-offset:2px;' : ''"
                    style="flex:0 0 17rem; border-radius:0.75rem; background:rgba(125,125,125,0.08); padding:0.75rem; min-height:12rem;">
                    <header style="margin-bottom:0.6rem;">
                        <div style="display:flex; justify-content:space-between; gap:0.5rem; font-weight:600;">
                            <span>{{ $stage->name }}</span>
                            <span style="opacity:0.7;">{{ $column['cards']->count() }}</span>
                        </div>
                        <div style="font-size:0.8rem; opacity:0.7;">
                            {{ $stage->probability }}%
                            @foreach ($column['totals'] as $currency => $total)
                                · {{ $currency }} {{ number_format($total, 0) }}
                            @endforeach
                        </div>
                    </header>

                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                        @forelse ($column['cards'] as $card)
                            <article
                                wire:key="opportunity-{{ $card->getKey() }}"
                                draggable="true"
                                x-on:dragstart="$event.dataTransfer.setData('text/plain', '{{ $card->getKey() }}'); $event.dataTransfer.effectAllowed = 'move'"
                                style="cursor:grab; border-radius:0.6rem; padding:0.6rem 0.7rem; background:var(--color-white, #fff); color:inherit; box-shadow:0 1px 2px rgba(0,0,0,0.12); border:1px solid rgba(125,125,125,0.2);"
                                class="dark:bg-gray-900">
                                <a href="{{ $this->opportunityUrl($card) }}" style="font-weight:600; text-decoration:none; color:inherit;">{{ $card->name }}</a>
                                @if ($card->organisation)
                                    <div style="font-size:0.85rem; opacity:0.8;">{{ $card->organisation->name }}</div>
                                @endif
                                <div style="font-size:0.8rem; opacity:0.75; margin-top:0.25rem;">
                                    @if ($card->value !== null){{ $card->currency }} {{ number_format((float) $card->value, 0) }} · @endif{{ $card->probability }}%
                                    @if ($card->expected_close_date) · {{ $card->expected_close_date->toFormattedDateString() }} @endif
                                    @if ($card->owner) · {{ $card->owner->name }} @endif
                                </div>
                                <label style="display:block; margin-top:0.4rem; font-size:0.75rem;">
                                    <span style="position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0);">{{ __('lite-crm::opportunities.board.move_to', ['name' => $card->name]) }}</span>
                                    <select wire:change="moveOpportunity({{ $card->getKey() }}, Number($event.target.value))"
                                        style="width:100%; font-size:0.75rem; border:1px solid rgba(125,125,125,0.35); border-radius:0.4rem; padding:0.15rem 1.5rem 0.15rem 0.4rem; background-color:transparent;">
                                        @foreach ($columns as $target)
                                            <option value="{{ $target['stage']->getKey() }}" @selected($target['stage']->getKey() === $card->stage_id)>
                                                {{ __('lite-crm::opportunities.board.move_option', ['stage' => $target['stage']->name]) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </article>
                        @empty
                            <p style="font-size:0.85rem; opacity:0.6;">{{ __('lite-crm::opportunities.board.empty') }}</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>

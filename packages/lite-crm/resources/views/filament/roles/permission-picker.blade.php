{{--
    Grouped permission picker for the role form. State: a list of permission names.
    Labels are readable ("Documents: view confidential"); the stored names never change.
--}}
@php
    $groups = \LiteCrm\Filament\Resources\Roles\RoleResource::permissionGroups();
    $total = array_sum(array_map(fn (array $group): int => count($group['permissions']), $groups));
@endphp

<x-filament-forms::field-wrapper :field="$field">
    <style>
        .lcrm-perm-grid { display: grid; gap: .125rem 1rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        @media (min-width: 640px) { .lcrm-perm-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .lcrm-perm-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { .lcrm-perm-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (min-width: 1536px) { .lcrm-perm-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        .lcrm-perm-item { display: flex; align-items: center; gap: .5rem; min-width: 0; padding: .25rem 0; cursor: pointer; }
        .lcrm-perm-item span { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: .875rem; }
        .lcrm-perm-group { border-top: 1px solid rgb(0 0 0 / .08); padding-top: .75rem; margin-top: .75rem; }
        .dark .lcrm-perm-group { border-top-color: rgb(255 255 255 / .1); }
        .lcrm-perm-head { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem 1rem; margin-bottom: .375rem; }
        .lcrm-perm-head h3 { font-weight: 600; font-size: .875rem; }
        .lcrm-perm-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }
        .lcrm-perm-toolbar input[type=search] { flex: 1 1 16rem; max-width: 24rem; }
        .lcrm-perm-link { font-size: .8125rem; font-weight: 500; color: var(--primary-600); cursor: pointer; background: none; border: 0; padding: 0; }
        .lcrm-perm-link:hover { text-decoration: underline; }
        .lcrm-perm-count { font-size: .8125rem; opacity: .7; }
    </style>

    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            search: '',
            groups: @js($groups),
            init() { if (! Array.isArray(this.state)) this.state = []; },
            matches(name, label) {
                const q = this.search.trim().toLowerCase();
                return q === '' || label.toLowerCase().includes(q) || name.toLowerCase().includes(q);
            },
            visible(area) {
                return Object.entries(this.groups[area].permissions).filter(([name, label]) => this.matches(name, label)).map(([name]) => name);
            },
            allVisible() { return Object.keys(this.groups).flatMap((area) => this.visible(area)); },
            select(names) { this.state = [...new Set([...(this.state ?? []), ...names])]; },
            deselect(names) { this.state = (this.state ?? []).filter((name) => ! names.includes(name)); },
        }"
        data-permission-picker
    >
        <div class="lcrm-perm-toolbar">
            <x-filament::input.wrapper style="flex: 1 1 16rem; max-width: 24rem;">
                <x-filament::input type="search" x-model.debounce.150ms="search" :placeholder="__('lite-crm::permissions.picker.search')" :aria-label="__('lite-crm::permissions.picker.search')" />
            </x-filament::input.wrapper>
            <button type="button" class="lcrm-perm-link" x-on:click="select(allVisible())">{{ __('lite-crm::permissions.picker.select_all') }}</button>
            <button type="button" class="lcrm-perm-link" x-on:click="deselect(allVisible())">{{ __('lite-crm::permissions.picker.deselect_all') }}</button>
            <span class="lcrm-perm-count" x-text="@js(__('lite-crm::permissions.picker.selected', ['count' => '__COUNT__', 'total' => $total])).replace('__COUNT__', (state ?? []).length)"></span>
        </div>

        @foreach ($groups as $area => $group)
            <section class="lcrm-perm-group" x-show="visible(@js($area)).length > 0" data-permission-group="{{ $area }}">
                <div class="lcrm-perm-head">
                    <h3>{{ $group['label'] }}</h3>
                    <button type="button" class="lcrm-perm-link" x-on:click="select(visible(@js($area)))">{{ __('lite-crm::permissions.picker.select_all') }}</button>
                    <button type="button" class="lcrm-perm-link" x-on:click="deselect(visible(@js($area)))">{{ __('lite-crm::permissions.picker.deselect_all') }}</button>
                </div>
                <div class="lcrm-perm-grid">
                    @foreach ($group['permissions'] as $name => $label)
                        <label class="lcrm-perm-item" title="{{ $label }} ({{ $name }})" x-show="matches(@js($name), @js($label))">
                            <x-filament::input.checkbox x-model="state" value="{{ $name }}" />
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <p class="lcrm-perm-count" style="margin-top: .75rem;" x-show="allVisible().length === 0" x-cloak>{{ __('lite-crm::permissions.picker.none_found') }}</p>
    </div>
</x-filament-forms::field-wrapper>

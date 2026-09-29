@props(['steps' => [], 'label' => null])
{{--
    A process flow, e.g. Nutrition → Product → Economics → Supply → Performance.
    An ordered list: arrows between steps on wide screens, numbered stack on phones.
--}}
<ol @if ($label) aria-label="{{ $label }}" @endif {{ $attributes->class(['flex flex-col gap-3 md:flex-row md:flex-wrap md:items-stretch md:gap-2']) }}>
    @foreach ($steps as $step)
        <li class="flex items-center gap-2 md:contents">
            <span class="flex flex-1 items-center gap-3 rounded-full border border-line bg-white px-5 py-3 font-semibold text-green-900 shadow-(--shadow-soft) md:flex-none">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-green-800 text-sm font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                {{ $step }}
            </span>
            @unless ($loop->last)
                <x-heroicon-m-arrow-right class="hidden size-5 shrink-0 self-center text-orange-500 md:block" aria-hidden="true" />
            @endunless
        </li>
    @endforeach
</ol>

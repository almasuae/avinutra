<x-filament-widgets::widget>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(20rem, 1fr)); gap:1rem;">
        @forelse ($summaries as $summary)
            <x-filament::section :heading="$summary['name']">
                <table style="width:100%; font-size:0.9rem;">
                    <thead>
                        <tr style="text-align:left; opacity:0.7;">
                            <th scope="col" style="padding:0.2rem 0;">{{ __('lite-crm::opportunities.fields.stage') }}</th>
                            <th scope="col" style="text-align:right;">#</th>
                            <th scope="col" style="text-align:right;">{{ __('lite-crm::opportunities.fields.value') }} ({{ $base }})</th>
                            <th scope="col" style="text-align:right;">{{ __('lite-crm::opportunities.fields.weighted_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['rows'] as $row)
                            <tr>
                                <td style="padding:0.2rem 0;">{{ $row['stage'] }}</td>
                                <td style="text-align:right;">{{ $row['count'] }}</td>
                                <td style="text-align:right;">{{ number_format($row['value'], 0) }}</td>
                                <td style="text-align:right;">{{ number_format($row['weighted'], 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:600; border-top:1px solid rgba(125,125,125,0.3);">
                            <td style="padding:0.3rem 0;">{{ __('lite-crm::dashboard.total') }}</td>
                            <td style="text-align:right;">{{ $summary['totals']['count'] }}</td>
                            <td style="text-align:right;">{{ number_format($summary['totals']['value'], 0) }}</td>
                            <td style="text-align:right;">{{ number_format($summary['totals']['weighted'], 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </x-filament::section>
        @empty
            <x-filament::section>{{ __('lite-crm::opportunities.board.no_pipelines') }}</x-filament::section>
        @endforelse
    </div>
    @if ($missingRates)
        <p style="margin-top:0.5rem; font-size:0.8rem; color:rgb(217 119 6);">{{ __('lite-crm::dashboard.missing_rates') }}</p>
    @endif
    @if ($staleRates !== '')
        <p style="margin-top:0.5rem; font-size:0.8rem; color:rgb(217 119 6);">{{ __('lite-crm::dashboard.stale_rates', ['days' => $staleDays, 'rates' => $staleRates]) }}</p>
    @endif
</x-filament-widgets::widget>

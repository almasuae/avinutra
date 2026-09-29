@props(['data'])
{{-- Structured data (JSON-LD). JSON_HEX_TAG keeps "</script>" out of the output; the nonce satisfies the CSP. --}}
<script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode(['@context' => 'https://schema.org', ...$data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

@php($site = app(\App\Settings\SiteSettings::class))
<x-page.legal title="Terms of Use" description="The terms that apply to using the AviNutra website.">
    <h2>About these terms</h2>
    <p>These terms apply to your use of this website, operated by {{ $site->brand }}. By using the website you accept them.</p>

    <h2>Information on this website</h2>
    <p>The content of this website is general technical and commercial information for feed-industry professionals. We take care to keep it accurate and to cite our sources, but it is not a substitute for advice on your particular situation. See our <a href="{{ route('legal.technical-disclaimer') }}">Technical Disclaimer</a>.</p>
    <p>Product information, including specifications and availability, may change. A product is offered for sale only in a written quotation, which states the contracting entity and the terms that apply.</p>

    <h2>Tools and calculators</h2>
    <p>Our calculators give indicative results based on the figures you enter and on default values that we label as such. You are responsible for the decisions you make using them.</p>

    <h2>Acceptable use</h2>
    <p>Please do not misuse the website: do not submit false or misleading information, attempt to gain unauthorised access, send spam through our forms or interfere with the website's operation.</p>

    <h2>Intellectual property</h2>
    <p>The content, design and logo of this website belong to {{ $site->brand }} unless stated otherwise. You may quote short passages with a clear reference and link to the source. Product names mentioned are used generically.</p>

    <h2>Links to other websites</h2>
    <p>We link to external sources, such as scientific publications and public registers, for reference. We are not responsible for their content.</p>

    <h2>Liability</h2>
    <p>To the extent permitted by law, we are not liable for loss arising from the use of this website or reliance on its content. Nothing in these terms limits liability that cannot be limited by law.</p>

    <h2>Changes and contact</h2>
    <p>We may update these terms; the date at the top shows the latest version. Questions: <a href="mailto:{{ $site->emails['info'] ?? '' }}">{{ $site->emails['info'] ?? '' }}</a>.</p>
</x-page.legal>

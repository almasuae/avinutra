@php($site = app(\App\Settings\SiteSettings::class))
<x-page.legal title="Privacy Policy" description="How AviNutra collects, uses and protects personal data submitted through this website.">
    <h2>Who is responsible for your data</h2>
    <p>The data controller is <strong>AviNutra</strong> (<a href="mailto:{{ $site->emails['info'] ?? '' }}">{{ $site->emails['info'] ?? '' }}</a>). Write to this address with any question about this policy or your personal data.</p>

    <h2>The laws we follow</h2>
    <p>We handle personal data in line with applicable data-protection laws. We follow their common principles: we collect only what we need, use it only for the purpose it was given for, keep it secure, and delete it when it is no longer needed.</p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>Enquiries and applications:</strong> the details you enter in our forms — typically your name, company, role, e-mail address, phone or WhatsApp number, country and city, and your message — and any files you attach.</li>
        <li><strong>Technical data:</strong> your IP address, the page you submitted a form from and the time of submission. We use these to protect our forms against spam and abuse.</li>
    </ul>
    <p>We do not use analytics or advertising services, and we do not use tracking cookies. See our <a href="{{ route('legal.cookies') }}">Cookie Notice</a>.</p>

    <h2>How we use it</h2>
    <ul>
        <li>to answer your enquiry, prepare quotations and provide the documents or samples you ask for;</li>
        <li>to evaluate supplier applications;</li>
        <li>to keep a record of our business communication with you;</li>
        <li>to protect this website against spam and misuse.</li>
    </ul>
    <p>We do not sell your personal data, and we do not send you marketing e-mails unless you have asked for them.</p>

    <h2>Where it is stored</h2>
    <p>Enquiries are held in our own customer-relationship system, on our own server. E-mails are sent from our own mail server. Files you upload are stored privately and are accessible only to authorised members of our team.</p>

    <h2>Who can see it</h2>
    <p>Only members of our team who need it to answer your enquiry. Where your enquiry concerns a product, we may share the necessary details with the manufacturer or with a partner involved in supplying the product, only to answer your enquiry or supply the product. Supplier documents are kept confidential; we are happy to sign an NDA on request.</p>

    <h2>How long we keep it</h2>
    <p>We keep enquiries and related correspondence for as long as needed for the business relationship and for our legal and accounting obligations, and then delete or anonymise them.</p>

    <h2>Your rights</h2>
    <p>You may ask to see the personal data we hold about you, to correct it, or to withdraw your consent and have it deleted where we are not required to keep it. Write to the address above; we will reply within a reasonable time.</p>

    <h2>Changes</h2>
    <p>We may update this policy. The date at the top shows when it was last changed.</p>
</x-page.legal>

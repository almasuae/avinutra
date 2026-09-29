@props(['form', 'values' => [], 'key' => null])
{{--
    A website enquiry form (App\Support\EnquiryForms), rendered by the Lite CRM
    component. Every submission becomes a CRM enquiry and is acknowledged by e-mail.
--}}
@php
    $settings = \App\Support\EnquiryForms::form($form, $values);
    $privacy = \App\Support\SiteLinks::url('legal.privacy');
@endphp
<livewire:lite-crm.enquiry-form
    :type="$settings['type']"
    :fields="$settings['fields']"
    :uploads="$settings['uploads']"
    :privacy-url="$privacy"
    :submit-label="$settings['submit']"
    :values="$settings['values']"
    :key="$key ?? 'enquiry-'.$form"
/>

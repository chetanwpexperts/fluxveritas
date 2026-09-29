@extends('layouts.public')
@section('title', 'Refund & Cancellation Policy')

@php
    $days  = config('plans.refund_window_days');
    $email = config('plans.seller.email');
@endphp

@section('content')
<main class="lp">
    <article class="lp-doc">
        <h1>Refund &amp; Cancellation Policy</h1>
        <p class="lp-updated">Last updated: 29 September 2026</p>

        <h2>How plans are billed</h2>
        <p>
            OutraqHQ has a Free plan and a paid Pro plan. Pro is paid in advance for one month or one year,
            in Indian Rupees, through Razorpay. Plans do not renew automatically — you are only charged
            when you choose to pay.
        </p>

        <h2>Full refund within {{ $days }} days</h2>
        <p>
            You can cancel any Pro payment within {{ $days }} days of making it and receive a full refund,
            including GST. The organization’s owner or an admin can do this from
            <strong>Billing → Change or cancel → Cancel &amp; refund</strong>.
        </p>
        <ul>
            <li>The refund goes back to the original payment method.</li>
            <li>It is usually credited within 5–7 working days, depending on your bank.</li>
            <li>The period that payment covered is removed. If no other paid time remains, your organization moves to the Free plan straight away.</li>
            <li>None of your data is deleted, and you can upgrade again at any time.</li>
        </ul>

        <h2>After {{ $days }} days</h2>
        <p>
            Payments older than {{ $days }} days are not refundable, including for unused time.
            You can still switch to Free from the Billing page: Pro stays active until the end of the
            period you paid for, and you will not be charged again.
        </p>

        <h2>Failed or duplicate payments</h2>
        <p>
            If money was deducted but the payment failed, your bank normally reverses it automatically
            within 5–7 working days. If you were charged twice for the same order, contact us and we will
            refund the duplicate in full.
        </p>

        <h2>Enterprise</h2>
        <p>Enterprise plans follow the refund and cancellation terms in your signed agreement.</p>

        <h2>Contact</h2>
        <p>
            Questions about a payment or refund? Reach us through the
            <a href="{{ route('contact') }}">contact page</a>@if($email) or at <a href="mailto:{{ $email }}">{{ $email }}</a>@endif,
            and include the receipt number shown on your Billing page.
        </p>
    </article>
</main>
@endsection

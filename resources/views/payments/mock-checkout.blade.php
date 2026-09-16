@extends('layouts.app', ['title' => 'Mock checkout'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Test mode</p><h1>Mock {{ ucfirst($payment->gateway) }} checkout</h1><p>No real gateway is configured, so this page simulates the hosted checkout. Nothing is charged.</p></div></header>
    <section class="section">
        <div class="shell content-layout">
            <p><strong>Amount:</strong> ${{ number_format((float) $payment->amount, 2) }} {{ strtoupper($payment->currency) }}</p>
            <p><strong>Reference:</strong> {{ $payment->gateway_reference }}</p>
            <p><strong>Payment status:</strong> {{ $payment->status }}</p>

            @if ($payment->status === 'pending')
                <p><a class="button button-primary" href="{{ $successUrl }}?mock_result=succeeded">Approve payment</a></p>
                <p><a class="button" href="{{ $successUrl }}?mock_result=declined">Decline payment</a></p>
                <p><a class="button" href="{{ $cancelUrl }}">Cancel and go back</a></p>
            @else
                <p>This payment has already been finalised.</p>
                <p><a class="button button-primary" href="{{ route('home') }}">Return home</a></p>
            @endif
        </div>
    </section>
@endsection

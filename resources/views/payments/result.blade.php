@extends('layouts.app', ['title' => 'Payment ' . ucfirst($outcome)])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Payment</p><h1>@if ($outcome === 'success' && $payment->status === 'succeeded')Thank you!@elseif ($outcome === 'cancelled')Payment cancelled@else Payment not completed @endif</h1></div></header>
    <section class="section">
        <div class="shell content-layout">
            @if ($outcome === 'success' && $payment->status === 'succeeded')
                <p>Your payment of ${{ number_format((float) $payment->amount, 2) }} was received successfully.</p>
            @elseif ($outcome === 'cancelled')
                <p>Your payment was cancelled. No charge was made.</p>
            @else
                <p>We were unable to confirm your payment. Please try again or contact us if you were charged.</p>
            @endif
            <p><a class="button button-primary" href="{{ route('home') }}">Return home</a></p>
        </div>
    </section>
@endsection

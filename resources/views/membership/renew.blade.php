@extends('layouts.app', ['title' => 'Renew Membership'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Membership</p><h1>Renew your membership</h1><p>Enter your email and membership number to renew for another year.</p></div></header>
    <section class="section">
        <div class="shell content-layout">
            <form class="contact-form" action="{{ route('membership.renew.store') }}" method="POST">
                @csrf
                @if ($errors->any())<div class="form-errors" role="alert"><strong>Please correct the highlighted fields.</strong></div>@endif
                <div class="form-row"><label>Email <span aria-hidden="true">*</span><input type="email" name="email" value="{{ old('email') }}" required>@error('email')<small>{{ $message }}</small>@enderror</label><label>Membership number <span aria-hidden="true">*</span><input name="membership_number" value="{{ old('membership_number') }}" required>@error('membership_number')<small>{{ $message }}</small>@enderror</label></div>
                <fieldset><legend>Payment method</legend><label class="choice"><input type="radio" name="gateway" value="stripe" @checked(old('gateway', 'stripe') === 'stripe')> Credit/debit card (Stripe)</label><label class="choice"><input type="radio" name="gateway" value="paypal" @checked(old('gateway') === 'paypal')> PayPal</label>@error('gateway')<small>{{ $message }}</small>@enderror</fieldset>
                <button class="button button-primary" type="submit">Continue to payment <span aria-hidden="true">&rarr;</span></button>
            </form>
        </div>
    </section>
@endsection

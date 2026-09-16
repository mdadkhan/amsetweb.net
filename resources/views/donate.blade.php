@extends('layouts.app', ['title' => 'Donate to AMSET'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Give</p><h1>Support AMSET</h1><p>Fund science education, professional programs, and community initiatives.</p></div></header>
    <section class="section">
        <div class="shell content-layout">
            <form class="contact-form" action="{{ route('donate.store') }}" method="POST">
                @csrf
                @if ($errors->any())<div class="form-errors" role="alert"><strong>Please correct the highlighted fields.</strong></div>@endif
                @if ($campaigns->isNotEmpty())
                    <label>Campaign
                        <select name="donation_campaign_id">
                            <option value="">General fund</option>
                            @foreach ($campaigns as $campaign)
                                <option value="{{ $campaign->id }}" @selected(old('donation_campaign_id') == $campaign->id)>{{ $campaign->title }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <div class="form-row"><label>Your name <span aria-hidden="true">*</span><input name="donor_name" value="{{ old('donor_name') }}" required>@error('donor_name')<small>{{ $message }}</small>@enderror</label><label>Email <span aria-hidden="true">*</span><input type="email" name="donor_email" value="{{ old('donor_email') }}" required>@error('donor_email')<small>{{ $message }}</small>@enderror</label></div>
                <div class="form-row"><label>Amount (USD) <span aria-hidden="true">*</span><input type="number" name="amount" min="1" step="0.01" value="{{ old('amount') }}" required>@error('amount')<small>{{ $message }}</small>@enderror</label>
                    <label>Frequency <span aria-hidden="true">*</span>
                        <select name="frequency" required>
                            <option value="one_time" @selected(old('frequency', 'one_time') === 'one_time')>One-time</option>
                            <option value="monthly" @selected(old('frequency') === 'monthly')>Monthly</option>
                            <option value="yearly" @selected(old('frequency') === 'yearly')>Yearly</option>
                        </select>
                    </label>
                </div>
                <fieldset><legend>Payment method</legend><label class="choice"><input type="radio" name="gateway" value="stripe" @checked(old('gateway', 'stripe') === 'stripe')> Credit/debit card (Stripe)</label><label class="choice"><input type="radio" name="gateway" value="paypal" @checked(old('gateway') === 'paypal')> PayPal</label>@error('gateway')<small>{{ $message }}</small>@enderror</fieldset>
                <button class="button button-primary" type="submit">Continue to payment <span aria-hidden="true">&rarr;</span></button>
            </form>
        </div>
    </section>
@endsection

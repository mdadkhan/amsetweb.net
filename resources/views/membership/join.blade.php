@extends('layouts.app', ['title' => 'Join AMSET'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Membership</p><h1>Join AMSET</h1><p>Select a membership plan to connect with the AMSET professional network.</p></div></header>
    <section class="section">
        <div class="shell content-layout">
            <form class="contact-form" action="{{ route('membership.store') }}" method="POST">
                @csrf
                @if ($errors->any())<div class="form-errors" role="alert"><strong>Please correct the highlighted fields.</strong></div>@endif
                <label>Membership plan <span aria-hidden="true">*</span>
                    <select name="membership_plan_id" id="membership-plan" required>
                        <option value="">Select a plan&hellip;</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" data-fee="{{ number_format($plan->fee(), 2, '.', '') }}" @selected(old('membership_plan_id') == $plan->id)>{{ $plan->name }} &mdash; ${{ number_format($plan->fee(), 2) }}/{{ $plan->billing_interval }}</option>
                        @endforeach
                    </select>
                    @error('membership_plan_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>Membership fee
                    <input id="membership-fee" type="text" value="&mdash;" readonly tabindex="-1" aria-describedby="membership-fee-note">
                    <small id="membership-fee-note">Set by AMSET and charged at checkout; it cannot be changed here.</small>
                </label>
                <div class="form-row"><label>First name <span aria-hidden="true">*</span><input name="first_name" value="{{ old('first_name') }}" required>@error('first_name')<small>{{ $message }}</small>@enderror</label><label>Last name <span aria-hidden="true">*</span><input name="last_name" value="{{ old('last_name') }}" required>@error('last_name')<small>{{ $message }}</small>@enderror</label></div>
                <div class="form-row"><label>Email <span aria-hidden="true">*</span><input type="email" name="email" value="{{ old('email') }}" required>@error('email')<small>{{ $message }}</small>@enderror</label><label>Phone<input name="phone" value="{{ old('phone') }}">@error('phone')<small>{{ $message }}</small>@enderror</label></div>
                <label>Organization<input name="organization" value="{{ old('organization') }}">@error('organization')<small>{{ $message }}</small>@enderror</label>
                <div class="form-row"><label>City<input name="city" value="{{ old('city') }}"></label><label>State/Province<input name="state" value="{{ old('state') }}"></label></div>
                <div class="form-row"><label>Postal code<input name="postal_code" value="{{ old('postal_code') }}"></label><label>Country<input name="country" value="{{ old('country') }}"></label></div>
                <fieldset><legend>Payment method</legend><label class="choice"><input type="radio" name="gateway" value="stripe" @checked(old('gateway', 'stripe') === 'stripe')> Credit/debit card (Stripe)</label><label class="choice"><input type="radio" name="gateway" value="paypal" @checked(old('gateway') === 'paypal')> PayPal</label>@error('gateway')<small>{{ $message }}</small>@enderror</fieldset>
                <button class="button button-primary" type="submit">Continue to payment <span aria-hidden="true">&rarr;</span></button>
            </form>
        </div>
    </section>
    <script>
        (function () {
            const plan = document.getElementById('membership-plan');
            const fee = document.getElementById('membership-fee');
            const render = () => {
                const selected = plan.options[plan.selectedIndex];
                const amount = selected ? selected.dataset.fee : null;
                fee.value = amount ? '$' + Number(amount).toFixed(2) : '\u2014';
            };
            plan.addEventListener('change', render);
            render();
        })();
    </script>
@endsection

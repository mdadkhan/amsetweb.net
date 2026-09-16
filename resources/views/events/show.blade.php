@extends('layouts.app', ['title' => $event->title])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Event</p><h1>{{ $event->title }}</h1><p>{{ $event->starts_at->toFormattedDateString() }}@if ($event->location) &middot; {{ $event->location }}@endif</p></div></header>
    <section class="section">
        <div class="shell content-layout">
            @if ($event->summary)<p>{{ $event->summary }}</p>@endif
            @if ($event->body)<div>{!! $event->body !!}</div>@endif

            @if ($event->registrationOpen())
                <form class="contact-form" action="{{ route('events.register', $event) }}" method="POST">
                    @csrf
                    @if ($errors->any())<div class="form-errors" role="alert"><strong>Please correct the highlighted fields.</strong></div>@endif
                    @if ($event->tickets->isNotEmpty())
                        <label>Ticket <span aria-hidden="true">*</span>
                            <select name="event_ticket_id">
                                @foreach ($event->tickets as $ticket)
                                    <option value="{{ $ticket->id }}">{{ $ticket->name }} &mdash; ${{ number_format((float) $ticket->price, 2) }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <div class="form-row"><label>Name <span aria-hidden="true">*</span><input name="name" value="{{ old('name') }}" required>@error('name')<small>{{ $message }}</small>@enderror</label><label>Email <span aria-hidden="true">*</span><input type="email" name="email" value="{{ old('email') }}" required>@error('email')<small>{{ $message }}</small>@enderror</label></div>
                    <div class="form-row"><label>Phone<input name="phone" value="{{ old('phone') }}"></label><label>Quantity <span aria-hidden="true">*</span><input type="number" name="quantity" min="1" max="20" value="{{ old('quantity', 1) }}" required></label></div>
                    <fieldset><legend>Payment method</legend><label class="choice"><input type="radio" name="gateway" value="stripe" @checked(old('gateway', 'stripe') === 'stripe')> Credit/debit card (Stripe)</label><label class="choice"><input type="radio" name="gateway" value="paypal" @checked(old('gateway') === 'paypal')> PayPal</label>@error('gateway')<small>{{ $message }}</small>@enderror</fieldset>
                    <button class="button button-primary" type="submit">Register <span aria-hidden="true">&rarr;</span></button>
                </form>
            @else
                <p><strong>Registration for this event is closed.</strong></p>
            @endif
        </div>
    </section>
@endsection

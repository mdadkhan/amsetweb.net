@extends('layouts.app', ['title' => 'Contact AMSET'])

@section('content')
    <section class="page-intro" aria-labelledby="contact-title">
        <div class="shell"><p class="eyebrow">Start a conversation</p><h1 id="contact-title">Contact AMSET</h1><p>Send a general inquiry or express your interest in AMSET membership. Our team will follow up using the email you provide.</p></div>
    </section>
    <section class="section">
        <div class="shell contact-grid">
            <aside class="contact-aside"><p class="eyebrow">Connect</p><h2>Expertise grows through exchange.</h2><p>Questions about AMSET, its programs, or membership are welcome.</p><a href="mailto:info@amsetweb.net">info@amsetweb.net</a></aside>
            <form class="contact-form" action="{{ route('contact.store') }}" method="POST">
                @csrf
                @if (session('status'))<div class="form-status" role="status">{{ session('status') }}</div>@endif
                @if ($errors->any())<div class="form-errors" role="alert"><strong>Please correct the highlighted fields.</strong></div>@endif
                <fieldset><legend>What can we help with?</legend><label class="choice"><input type="radio" name="inquiry_type" value="general" @checked(old('inquiry_type', 'general') === 'general')> General inquiry</label><label class="choice"><input type="radio" name="inquiry_type" value="membership" @checked(old('inquiry_type') === 'membership')> Membership</label></fieldset>
                <div class="form-row"><label>Name <span aria-hidden="true">*</span><input name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<small>{{ $message }}</small>@enderror</label><label>Email <span aria-hidden="true">*</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<small>{{ $message }}</small>@enderror</label></div>
                <div class="form-row"><label>Phone<input name="phone" value="{{ old('phone') }}" autocomplete="tel">@error('phone')<small>{{ $message }}</small>@enderror</label><label>Organization<input name="organization" value="{{ old('organization') }}" autocomplete="organization">@error('organization')<small>{{ $message }}</small>@enderror</label></div>
                <label>Message <span aria-hidden="true">*</span><textarea name="message" rows="7" required>{{ old('message') }}</textarea>@error('message')<small>{{ $message }}</small>@enderror</label>
                <button class="button button-primary" type="submit">Send message <span aria-hidden="true">→</span></button>
            </form>
        </div>
    </section>
@endsection

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="AMSET connects Muslim scientists, engineers, and technology professionals through advocacy, education, and service.">
        <title>{{ $title ?? 'AMSET' }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <a class="skip-link" href="#main-content">Skip to content</a>
        <header class="site-header">
            <div class="shell header-inner">
                <a class="brand" href="{{ route('home') }}" aria-label="AMSET home">
                    <img class="brand-logo" src="{{ asset('images/amset-logo.png') }}" width="66" height="100" alt="AMSET">
                    <span class="brand-copy"><strong>Association of Muslim Scientists, Engineers &amp; Technology Professionals</strong><small>Science. Engineering. Technology.</small></span>
                </a>
                <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" data-nav-toggle>
                    <span class="sr-only">Toggle navigation</span>
                    <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
                </button>
                <nav id="primary-navigation" class="primary-nav" aria-label="Primary navigation" data-navigation>
                    <a href="{{ route('about') }}">About</a><a href="{{ route('conferences') }}">Conferences</a><a href="{{ route('news') }}">News</a><a href="{{ route('scientists') }}">Scientists</a><a href="{{ route('gallery') }}">Gallery</a><a href="{{ route('news') }}#future-holds">Future Holds</a><a href="{{ route('events.index') }}">Events</a><a href="{{ route('membership.join') }}">Membership</a><a href="{{ route('contact.create') }}">Contact</a><a class="nav-action" href="{{ route('donate.create') }}">Donate</a>
                </nav>
            </div>
        </header>
        <main id="main-content">@yield('content')</main>
        <footer class="site-footer" id="contact">
            <div class="shell footer-grid">
                <div><a class="brand brand-footer" href="{{ route('home') }}"><img class="brand-logo brand-logo-footer" src="{{ asset('images/amset-logo.png') }}" width="66" height="100" alt="AMSET"><strong>AMSET</strong></a><p>Association of Muslim Scientists, Engineers &amp; Technology Professionals</p></div>
                <div><strong>Connect</strong><a href="mailto:info@amsetweb.net">info@amsetweb.net</a></div>
                <p class="copyright">&copy; {{ date('Y') }} AMSET. All rights reserved.</p>
            </div>
        </footer>
    </body>
</html>

@extends('layouts.app', ['title' => 'AMSET Events'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Events</p><h1>Upcoming AMSET events</h1><p>Conferences, webinars, science fairs, and hackathons.</p></div></header>
    <section class="section">
        <div class="shell content-layout">
            <div class="archive-list">
                @forelse ($events as $event)
                    <article class="archive-item"><h2><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a></h2><p>{{ $event->starts_at->toFormattedDateString() }}@if ($event->location) &middot; {{ $event->location }}@endif</p>@if ($event->summary)<p>{{ $event->summary }}</p>@endif</article>
                @empty
                    <p>No upcoming events are scheduled right now.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection

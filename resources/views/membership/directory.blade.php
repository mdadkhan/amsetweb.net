@extends('layouts.app', ['title' => 'Membership Directory'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Membership</p><h1>Membership directory</h1><p>Current AMSET members in good standing.</p></div></header>
    <section class="section">
        <div class="shell content-layout">
            <div class="gallery-list">
                @forelse ($members as $member)
                    <article class="gallery-album"><h2>{{ $member->fullName() }}</h2>@if ($member->organization)<p>{{ $member->organization }}</p>@endif @if ($member->city || $member->state)<p>{{ collect([$member->city, $member->state])->filter()->implode(', ') }}</p>@endif</article>
                @empty
                    <p>No members to display yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection

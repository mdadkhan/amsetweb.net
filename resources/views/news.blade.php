@extends('layouts.app', ['title' => 'AMSET News'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">News &amp; initiatives</p><h1>The work in motion.</h1><p>Updates from AMSET programs, committees, and the wider scientific community.</p></div></header>
    <section class="section"><div class="shell content-layout"><div class="archive-list">@forelse ($posts as $post)<article class="archive-item"><p class="item-meta">{{ $post->published_at?->format('M j, Y') }} · {{ ucfirst($post->category) }}</p><h2>{{ $post->title }}</h2><p>{{ $post->excerpt }}</p></article>@empty<p>No news has been published yet.</p>@endforelse</div><aside class="section-index"><strong>News</strong><a href="{{ route('scientists') }}">Eminent Scientists</a><a href="#future-holds">Future Holds</a><a href="{{ route('conferences') }}">Conferences</a></aside></div></section>
    @if ($future)<section class="section history-band" id="future-holds"><div class="shell"><p class="eyebrow">Future Holds</p><h2>{{ $future->title }}</h2><p class="lead-copy">{{ $future->summary }}</p>@if ($future->body)<div class="lead-copy">{!! $future->body !!}</div>@endif</div></section>@endif
@endsection

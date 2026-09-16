@extends('layouts.app', ['title' => 'AMSET Gallery'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Gallery</p><h1>Programs, people, and shared moments.</h1><p>Event albums organized by conference, initiative, and occasion.</p></div></header>
    <section class="section"><div class="shell content-layout"><div class="gallery-list">@forelse ($galleries as $gallery)<article class="gallery-album" id="{{ $gallery->slug }}"><p class="item-meta">{{ $gallery->event_date?->format('F Y') ?? 'AMSET archive' }}</p><h2>{{ $gallery->title }}</h2><p>{{ $gallery->description }}</p><div class="gallery-grid">@foreach ($gallery->items as $item)<figure><img src="{{ Str::startsWith($item->image, ['http://', 'https://']) ? $item->image : asset($item->image) }}" alt="{{ $item->caption ?: $gallery->title }}"><figcaption>{{ $item->caption }}</figcaption></figure>@endforeach</div></article>@empty<p>Gallery albums are being prepared.</p>@endforelse</div><aside class="section-index"><strong>Event albums</strong>@foreach ($galleries as $gallery)<a href="#{{ $gallery->slug }}">{{ $gallery->title }}</a>@endforeach</aside></div></section>
@endsection

@extends('layouts.app', ['title' => 'AMSET Conferences'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Conference archive</p><h1>Ideas shared across generations.</h1><p>Programs, presentations, and records from AMSET gatherings.</p></div></header>
    <section class="section"><div class="shell archive-list">@foreach ($conferences as $conference)<article class="archive-item"><p class="item-meta">{{ $conference->starts_at?->format('Y') ?? Str::match('/\b(?:19|20)\d{2}\b/', $conference->title) ?: 'Archive' }}{{ $conference->location ? ' · '.$conference->location : '' }}</p><h2>{{ $conference->title }}</h2><p>{{ $conference->summary }}</p>@if ($conference->program_url)<a class="text-link" href="{{ $conference->program_url }}">View program <span aria-hidden="true">→</span></a>@endif</article>@endforeach</div></section>
@endsection

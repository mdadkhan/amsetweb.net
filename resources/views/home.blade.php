@extends('layouts.app', ['title' => 'AMSET | Building what comes next'])

@section('content')
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-grid" aria-hidden="true"></div>
        <div class="shell hero-content">
            <p class="eyebrow">Established 1963 · Built for what comes next</p>
            <h1 id="hero-title">Where scientific rigor meets public purpose.</h1>
            <p class="hero-copy">A professional community advancing responsible technology, strengthening Muslim leadership, and creating pathways for the next generation of innovators.</p>
            <div class="hero-actions"><a class="button button-primary" href="#initiatives">Explore our work <span aria-hidden="true">→</span></a><a class="button button-secondary" href="#about">Discover AMSET</a></div>
            <dl class="signal-strip" aria-label="AMSET at a glance"><div><dt>63</dt><dd>years of legacy</dd></div><div><dt>STEM</dt><dd>across disciplines</dd></div><div><dt>Future</dt><dd>focused community</dd></div></dl>
        </div>
    </section>

    <section class="initiatives section" id="initiatives" aria-labelledby="initiatives-title">
        <div class="shell">
            <div class="section-heading"><div><p class="eyebrow">Active signals</p><h2 id="initiatives-title">Ideas into impact</h2></div><p>Programs that connect expertise with the questions shaping our communities.</p></div>
            <div class="initiative-grid">
                @foreach ($initiatives as $index => $initiative)
                    <a class="initiative" id="{{ ltrim($initiative->href, '#') }}" href="{{ $initiative->href }}"><span class="initiative-number">0{{ $index + 1 }}</span><span class="initiative-eyebrow">{{ $initiative->eyebrow }}</span><strong>{{ $initiative->name }}</strong><span class="initiative-arrow" aria-hidden="true">↗</span></a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="about-band section" id="about" aria-labelledby="about-title">
        <div class="shell about-grid">
            <div class="about-visual" role="img" aria-label="Abstract network representing connected science and technology disciplines"><span class="orbit orbit-one"></span><span class="orbit orbit-two"></span><span class="core">AMSET</span></div>
            <div class="about-copy"><p class="eyebrow">A multidisciplinary network</p><h2 id="about-title">Built by people who ask better questions.</h2><p>AMSET brings together scientists, engineers, technologists, educators, and emerging professionals to exchange knowledge and turn expertise into service.</p><a class="text-link" href="#legacy">AMSE to AMSET: our history <span aria-hidden="true">→</span></a></div>
        </div>
    </section>

    <section class="news section" id="news" aria-labelledby="news-title">
        <div class="shell">
            <div class="section-heading"><div><p class="eyebrow">From the network</p><h2 id="news-title">Latest news</h2></div><p>Updates from AMSET programs, scientists, and future-focused initiatives.</p></div>
            @if ($posts->isEmpty())
                <div class="news-empty"><span class="status-dot" aria-hidden="true"></span><div><h3>New dispatches are in preparation.</h3><p>AMSET committee members are curating the first set of stories and announcements.</p></div></div>
            @else
                <div class="news-grid">@foreach ($posts as $post)<article class="news-item"><p>{{ $post->category }}</p><h3>{{ $post->title }}</h3><p>{{ $post->excerpt }}</p></article>@endforeach</div>
            @endif
        </div>
    </section>

    <section class="legacy section" id="legacy" aria-labelledby="legacy-title">
        <div class="shell legacy-grid"><div><p class="eyebrow">AMSE → AMSET</p><h2 id="legacy-title">A 63-year continuum of Muslim excellence.</h2></div><div class="timeline-preview"><div><span>1963</span><p>A professional community takes root.</p></div><div><span>Today</span><p>AMSET expands its focus for an era shaped by AI and emerging technology.</p></div></div></div>
    </section>

    <section class="contribute section" id="contribute" aria-labelledby="contribute-title">
        <div class="shell contribute-inner"><div><p class="eyebrow">Invest in possibility</p><h2 id="contribute-title">Help the next idea find its momentum.</h2></div><a class="button button-light" href="mailto:info@amsetweb.net?subject=Contribute%20to%20AMSET">Contribute to AMSET <span aria-hidden="true">→</span></a></div>
    </section>
@endsection

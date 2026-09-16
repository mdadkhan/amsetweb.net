@extends('layouts.app', ['title' => 'Eminent Scientists | AMSET'])

@section('content')
    <header class="page-intro"><div class="shell"><p class="eyebrow">Eminent scientists</p><h1>People expanding what is possible.</h1><p>Scientists and AMSET community members whose work informs, challenges, and serves.</p></div></header>
    <section class="section"><div class="shell content-layout"><div class="profile-grid">@foreach ($scientists as $person)<article class="profile" id="{{ $person->slug }}"><p class="item-meta">{{ $person->expertise ?: $person->role }}</p><h2>{{ $person->name }}</h2><p>{{ $person->institution }}</p><p>{{ $person->biography }}</p></article>@endforeach @foreach ($legacyProfiles as $profile)<article class="profile" id="{{ $profile->slug }}"><p class="item-meta">Legacy profile</p><h2>{{ $profile->title }}</h2><p>{{ $profile->excerpt }}</p></article>@endforeach</div><aside class="section-index"><strong>Scientist index</strong>@foreach ($scientists as $person)<a href="#{{ $person->slug }}">{{ $person->name }}</a>@endforeach @foreach ($legacyProfiles as $profile)<a href="#{{ $profile->slug }}">{{ $profile->title }}</a>@endforeach</aside></div></section>
@endsection

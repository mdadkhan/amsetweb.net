<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Person;
use App\Models\Post;
use App\Models\TimelineEvent;
use Illuminate\Contracts\View\View;

class PublicContentController extends Controller
{
    public function about(): View
    {
        return view('about', [
            'page' => Page::query()->where('slug', 'about-us')->where('is_published', true)->first(),
            'team' => Person::query()->published()->where('type', 'team')->get(),
            'timeline' => TimelineEvent::query()->published()->get(),
        ]);
    }

    public function conferences(): View
    {
        return view('conferences', [
            'conferences' => Conference::query()->published()->get(),
        ]);
    }

    public function news(): View
    {
        return view('news', [
            'posts' => Post::query()->published()->whereIn('category', ['news', 'about'])->latest('published_at')->get(),
            'future' => Page::query()->where('slug', 'future-holds')->where('is_published', true)->first(),
        ]);
    }

    public function scientists(): View
    {
        return view('scientists', [
            'scientists' => Person::query()->published()->where('type', 'scientist')->get(),
            'legacyProfiles' => Post::query()->published()->where('category', 'scientist')->get(),
        ]);
    }

    public function gallery(): View
    {
        return view('gallery', [
            'galleries' => Gallery::query()->published()->with('items')->get(),
        ]);
    }
}

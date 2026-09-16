<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Models\Post;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'initiatives' => Initiative::query()->published()->get(),
            'posts' => Post::query()->published()->latest('published_at')->limit(3)->get(),
        ]);
    }
}

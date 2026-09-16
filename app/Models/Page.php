<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'summary', 'body', 'featured_image', 'meta', 'is_published',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_published' => 'boolean'];
    }
}

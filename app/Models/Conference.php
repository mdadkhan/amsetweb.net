<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Conference extends Model
{
    protected $fillable = [
        'title', 'slug', 'starts_at', 'ends_at', 'location', 'summary', 'body', 'program_url', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->latest('starts_at');
    }
}

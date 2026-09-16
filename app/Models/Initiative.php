<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Initiative extends Model
{
    protected $fillable = [
        'name', 'slug', 'eyebrow', 'description', 'link', 'icon', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }

    public function getHrefAttribute(): string
    {
        return $this->link ?: '#'.$this->slug;
    }
}

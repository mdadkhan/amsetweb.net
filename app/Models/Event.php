<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'title', 'slug', 'summary', 'body', 'location', 'starts_at', 'ends_at',
        'registration_deadline', 'capacity', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_deadline' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class)->orderBy('sort_order');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('starts_at');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    public function registrationOpen(): bool
    {
        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return false;
        }

        if ($this->capacity === null) {
            return true;
        }

        return $this->registrations()->whereIn('status', ['registered', 'checked_in'])->sum('quantity') < $this->capacity;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', 'billing_interval', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * The amount actually charged: the env-configured fee for this slug, falling
     * back to the stored price when the slug is not configured.
     */
    public function fee(): float
    {
        $configured = config('payments.membership_fees.'.$this->slug);

        return (float) ($configured ?? $this->price);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}

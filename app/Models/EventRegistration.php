<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id', 'event_ticket_id', 'member_id', 'confirmation_code', 'name', 'email', 'phone',
        'quantity', 'amount_due', 'status', 'checked_in_at',
    ];

    protected function casts(): array
    {
        return ['amount_due' => 'decimal:2', 'checked_in_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(EventTicket::class, 'event_ticket_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}

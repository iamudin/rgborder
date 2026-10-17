<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waitlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'user_id',
        'player_name',
        'player_phone',
        'player_count',
        'queue_number',
        'status',
        'offered_at',
        'offer_expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'player_count' => 'integer',
            'queue_number' => 'integer',
            'offered_at' => 'datetime',
            'offer_expires_at' => 'datetime',
        ];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isOffered(): bool
    {
        return $this->status === 'offered';
    }

    public function isOfferExpired(): bool
    {
        return $this->isOffered() && $this->offer_expires_at && Carbon::now()->isAfter($this->offer_expires_at);
    }

    public function offerSlot(int $minutes = 15): void
    {
        $this->update([
            'status' => 'offered',
            'offered_at' => Carbon::now(),
            'offer_expires_at' => Carbon::now()->addMinutes($minutes),
        ]);
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'user_id',
        'game_session_id',
        'booking_type',
        'target_team_id',
        'total_players',
        'price_per_player',
        'total_amount',
        'status',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_players' => 'integer',
            'price_per_player' => 'integer',
            'total_amount' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function targetTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'target_team_id');
    }

    public function bookingPlayers(): HasMany
    {
        return $this->hasMany(BookingPlayer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isHoldExpired(): bool
    {
        return $this->isPendingPayment() && $this->expires_at && Carbon::now()->isAfter($this->expires_at);
    }

    public function getRemainingHoldSecondsAttribute(): int
    {
        if (! $this->isPendingPayment() || ! $this->expires_at) {
            return 0;
        }

        $remaining = Carbon::now()->diffInSeconds($this->expires_at, false);

        return max(0, (int) $remaining);
    }

    public function getFormattedBookingTypeAttribute(): string
    {
        return match ($this->booking_type) {
            'group' => "Kelompok ({$this->total_players} Pemain)",
            'full_team' => 'Tim Penuh (4 Pemain)',
            default => 'Individual (1 Pemain)',
        };
    }
}

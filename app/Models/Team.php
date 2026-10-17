<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'name',
        'color',
        'color_code',
        'max_players',
    ];

    protected function casts(): array
    {
        return [
            'max_players' => 'integer',
        ];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function bookingPlayers(): HasMany
    {
        return $this->hasMany(BookingPlayer::class);
    }

    public function activeBookingPlayers(): HasMany
    {
        return $this->hasMany(BookingPlayer::class)
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', ['confirmed', 'pending_payment']);
            });
    }

    public function confirmedBookingPlayers(): HasMany
    {
        return $this->hasMany(BookingPlayer::class)
            ->whereHas('booking', function ($query) {
                $query->where('status', 'confirmed');
            });
    }

    public function pendingBookingPlayers(): HasMany
    {
        return $this->hasMany(BookingPlayer::class)
            ->whereHas('booking', function ($query) {
                $query->where('status', 'pending_payment');
            });
    }

    public function getOccupiedSlotsCountAttribute(): int
    {
        return $this->activeBookingPlayers()->count();
    }

    public function getConfirmedSlotsCountAttribute(): int
    {
        return $this->confirmedBookingPlayers()->count();
    }

    public function getPendingSlotsCountAttribute(): int
    {
        return $this->pendingBookingPlayers()->count();
    }

    public function getAvailableSlotsCountAttribute(): int
    {
        return max(0, $this->max_players - $this->occupied_slots_count);
    }

    public function isFull(): bool
    {
        return $this->available_slots_count <= 0;
    }
}

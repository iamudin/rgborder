<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_code',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'price_per_player',
        'max_players',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'duration_minutes' => 'integer',
            'price_per_player' => 'integer',
            'max_players' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activeBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->whereIn('status', ['confirmed', 'pending_payment']);
    }

    public function confirmedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', 'confirmed');
    }

    public function bookingPlayers(): HasManyThrough
    {
        return $this->hasManyThrough(BookingPlayer::class, Booking::class);
    }

    public function waitlists(): HasMany
    {
        return $this->hasMany(Waitlist::class);
    }

    public function getFormattedTimeRangeAttribute(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);

        return "{$start} - {$end}";
    }

    public function getRedTeamAttribute(): ?Team
    {
        return $this->teams->firstWhere('color', 'red') ?? $this->teams->first();
    }

    public function getBlueTeamAttribute(): ?Team
    {
        return $this->teams->firstWhere('color', 'blue') ?? $this->teams->skip(1)->first();
    }

    public function getOccupiedSlotsCountAttribute(): int
    {
        return $this->teams->sum(fn($team) => $team->occupied_slots_count);
    }

    public function getConfirmedSlotsCountAttribute(): int
    {
        return $this->teams->sum(fn($team) => $team->confirmed_slots_count);
    }

    public function getPendingSlotsCountAttribute(): int
    {
        return $this->teams->sum(fn($team) => $team->pending_slots_count);
    }

    public function getAvailableSlotsCountAttribute(): int
    {
        return max(0, $this->max_players - $this->occupied_slots_count);
    }

    public function getConfirmedRevenueAttribute(): int
    {
        return (int) $this->confirmedBookings()->sum('total_amount');
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_FULL = 'full';
    public const STATUS_READY = 'ready';
    public const STATUS_PLAYING = 'playing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_OPEN => 'Open',
            self::STATUS_FULL => 'Full',
            self::STATUS_READY => 'Ready (Siap)',
            self::STATUS_PLAYING => 'Playing (Berlangsung)',
            self::STATUS_COMPLETED => 'Completed (Selesai)',
            self::STATUS_CANCELLED => 'Cancelled (Batal)',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->available_slots_count > 0;
    }

    public function isFull(): bool
    {
        return $this->status === 'full' || ($this->status === 'open' && $this->available_slots_count <= 0);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPastTime(): bool
    {
        $sessionStart = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time);

        return $sessionStart->isPast();
    }

    public function canBeBooked(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        if ($this->available_slots_count <= 0) {
            return false;
        }

        if ($this->isPastTime()) {
            return false;
        }

        return true;
    }

    public function scopeBookable($query)
    {
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->format('H:i:s');

        return $query->where('status', 'open')
            ->where(function ($q) use ($today, $currentTime) {
                $q->where('date', '>', $today)
                    ->orWhere(function ($todayQuery) use ($today, $currentTime) {
                        $todayQuery->where('date', $today)
                            ->where('start_time', '>', $currentTime);
                    });
            });
    }

    public static function getNextBookableSession(): ?self
    {
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->format('H:i:s');

        return static::with(['teams.bookingPlayers.booking'])
            ->where('status', 'open')
            ->where(function ($q) use ($today, $currentTime) {
                $q->where('date', '>', $today)
                    ->orWhere(function ($todayQuery) use ($today, $currentTime) {
                        $todayQuery->where('date', $today)
                            ->where('start_time', '>', $currentTime);
                    });
            })
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->first(fn($session) => $session->canBeBooked());
    }

    public function getRemainingSeconds(): int
    {
        if (!$this->isPlaying() || !$this->started_at) {
            return 0;
        }

        $totalSeconds = $this->duration_minutes * 60;
        $elapsed = Carbon::now()->diffInSeconds($this->started_at, false);

        if ($elapsed < 0) {
            $elapsed = abs($elapsed);
        } else {
            $elapsed = Carbon::now()->diffInSeconds($this->started_at);
        }

        return max(0, $totalSeconds - (int) $elapsed);
    }

    public function getTimerProgressPercentage(): float
    {
        if (!$this->isPlaying() || !$this->started_at) {
            return 0.0;
        }

        $totalSeconds = $this->duration_minutes * 60;
        $remaining = $this->getRemainingSeconds();

        return round((($totalSeconds - $remaining) / $totalSeconds) * 100, 1);
    }
}

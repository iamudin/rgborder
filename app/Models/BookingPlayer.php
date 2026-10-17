<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'player_profile_id',
        'team_id',
        'player_name',
        'player_phone',
        'check_in_status',
        'check_in_token',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BookingPlayer $bookingPlayer) {
            if (empty($bookingPlayer->check_in_token)) {
                $bookingPlayer->check_in_token = 'CHK-'.Str::random(16);
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function isCheckedIn(): bool
    {
        return $this->check_in_status === 'checked_in';
    }

    public function checkIn(): bool
    {
        if ($this->isCheckedIn()) {
            return false;
        }

        $this->update([
            'check_in_status' => 'checked_in',
            'checked_in_at' => Carbon::now(),
        ]);

        return true;
    }
}

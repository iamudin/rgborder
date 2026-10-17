<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_method',
        'amount',
        'status',
        'proof_file',
        'reference_number',
        'notes',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPendingVerification(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    public function markAsPaid(?User $verifier = null, ?string $reference = null): void
    {
        $this->update([
            'status' => 'paid',
            'verified_at' => Carbon::now(),
            'verified_by' => $verifier?->id,
            'reference_number' => $reference ?? $this->reference_number,
        ]);

        $this->booking->update(['status' => 'confirmed']);
    }
}

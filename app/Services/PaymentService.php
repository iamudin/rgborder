<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected SessionService $sessionService
    ) {}

    /**
     * Submit payment proof (e.g. customer uploaded transfer receipt / QRIS receipt).
     *
     * @param Payment $payment
     * @param UploadedFile $proofImage
     * @param string|null $referenceNumber
     * @return Payment
     * @throws ValidationException
     */
    public function submitPaymentProof(Payment $payment, UploadedFile $proofImage, ?string $referenceNumber = null): Payment
    {
        if (in_array($payment->status, ['paid', 'expired', 'refunded'])) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran ini sudah tidak dapat menerima bukti pembayaran.',
            ]);
        }

        $path = $proofImage->store('payment-proofs', 'public');

        $payment->update([
            'status' => 'pending_verification',
            'proof_file' => $path,
            'reference_number' => $referenceNumber ?? $payment->reference_number,
        ]);

        ActivityLog::record('payment.proof_uploaded', $payment, [
            'booking_code' => $payment->booking->booking_code,
            'amount' => $payment->amount,
            'method' => $payment->payment_method,
        ], $payment->booking->user);

        return $payment;
    }

    /**
     * Verify and confirm payment by Arena Admin/Operator.
     *
     * @param Payment $payment
     * @param User $verifier
     * @param string|null $referenceNumber
     * @return bool
     * @throws ValidationException
     */
    public function verifyPayment(Payment $payment, User $verifier, ?string $referenceNumber = null): bool
    {
        if ($payment->isPaid()) {
            return true; // Already verified
        }

        return DB::transaction(function () use ($payment, $verifier, $referenceNumber) {
            $payment->update([
                'status' => 'paid',
                'verified_at' => Carbon::now(),
                'verified_by' => $verifier->id,
                'reference_number' => $referenceNumber ?? $payment->reference_number,
            ]);

            $booking = $payment->booking;
            $booking->update([
                'status' => 'confirmed',
                'expires_at' => null, // Hold is permanently secured
            ]);

            $session = $booking->gameSession;
            $this->sessionService->syncSessionStatus($session);

            ActivityLog::record('payment.verified', $payment, [
                'booking_code' => $booking->booking_code,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'verifier' => $verifier->name,
            ], $verifier);

            return true;
        });
    }

    /**
     * Reject invalid payment proof.
     *
     * @param Payment $payment
     * @param string $reason
     * @param User $verifier
     * @return bool
     */
    public function rejectPayment(Payment $payment, string $reason, User $verifier): bool
    {
        $payment->update([
            'status' => 'failed',
            'notes' => trim(($payment->notes ?? '')."\nDitolak: ".$reason),
        ]);

        ActivityLog::record('payment.rejected', $payment, [
            'booking_code' => $payment->booking->booking_code,
            'reason' => $reason,
        ], $verifier);

        return true;
    }
}

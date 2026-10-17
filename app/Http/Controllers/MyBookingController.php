<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\BookingService;
use App\Services\CheckInService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MyBookingController extends Controller
{
    public function index(): View
    {
        $bookings = Booking::with(['gameSession.teams', 'bookingPlayers.team', 'payments'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function show(string $code, CheckInService $checkInService): View|RedirectResponse
    {
        $booking = Booking::with([
            'gameSession.teams',
            'bookingPlayers.team',
            'payments.verifiedBy',
            'user',
        ])
            ->where('booking_code', $code)
            ->firstOrFail();

        // Check authorization: must be the booking owner or admin
        if (Auth::id() !== $booking->user_id && ! Auth::user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat booking ini.');
        }

        // Generate QR Code SVG for each player if booking is confirmed
        $playerQrs = [];
        if ($booking->isConfirmed()) {
            foreach ($booking->bookingPlayers as $player) {
                $checkInUrl = $checkInService->getCheckInUrl($player->check_in_token);
                $playerQrs[$player->id] = $checkInService->generateQrCodeSvg($checkInUrl);
            }
        }

        $bankName = Setting::get('bank_name', 'BCA');
        $bankNumber = Setting::get('bank_account_number', '8830192831');
        $bankHolder = Setting::get('bank_account_holder', 'RGB ARENA INDONESIA');
        $qrisImage = Setting::get('qris_image_path', '/images/qris-rgb-arena.svg');

        return view('bookings.show', compact('booking', 'playerQrs', 'bankName', 'bankNumber', 'bankHolder', 'qrisImage'));
    }

    public function uploadPaymentProof(Request $request, string $code, PaymentService $paymentService): RedirectResponse
    {
        $booking = Booking::where('booking_code', $code)->firstOrFail();

        if (Auth::id() !== $booking->user_id && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'proof_file' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'reference_number' => ['nullable', 'string', 'max:50'],
        ], [
            'proof_file.required' => 'File bukti pembayaran wajib diunggah.',
            'proof_file.image' => 'File bukti pembayaran harus berupa gambar.',
            'proof_file.max' => 'Ukuran file bukti pembayaran maksimal 4MB.',
        ]);

        $payment = $booking->latestPayment;
        if (! $payment) {
            return redirect()->back()->with('error', 'Transaksi pembayaran tidak ditemukan.');
        }

        try {
            $paymentService->submitPaymentProof($payment, $request->file('proof_file'), $request->input('reference_number'));

            return redirect()->back()->with('success', 'Bukti pembayaran berhasil diunggah! Petugas arena akan memverifikasi dalam waktu singkat.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal mengunggah bukti: '.$e->getMessage());
        }
    }

    public function cancel(Request $request, string $code, BookingService $bookingService): RedirectResponse
    {
        $booking = Booking::where('booking_code', $code)->firstOrFail();

        if (Auth::id() !== $booking->user_id && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $reason = $request->input('reason', 'Dibatalkan oleh pelanggan');

        if ($bookingService->cancelBooking($booking, $reason, Auth::user())) {
            return redirect()->route('my-bookings.show', $code)->with('success', 'Booking berhasil dibatalkan dan slot telah dilepaskan.');
        }

        return redirect()->back()->with('error', 'Booking tidak dapat dibatalkan.');
    }
}

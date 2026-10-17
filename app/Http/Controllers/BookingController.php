<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): RedirectResponse
    {
        // Automatically select the closest upcoming open session (ignoring past hours)
        $nextSession = GameSession::getNextBookableSession();

        if ($nextSession) {
            return redirect()->route('booking.show', $nextSession);
        }

        return redirect()->route('schedule')->with('info', 'Tidak ada sesi terbuka yang akan datang saat ini. Silakan periksa jadwal hari berikutnya.');
    }

    public function show(GameSession $session): View|RedirectResponse
    {
        // If session has already passed or cannot be booked, redirect to the next closest available session
        if (! $session->canBeBooked()) {
            $nextSession = GameSession::getNextBookableSession();

            if ($nextSession && $nextSession->id !== $session->id) {
                return redirect()->route('booking.show', $nextSession)
                    ->with('warning', "Sesi {$session->formatted_time_range} WIB sudah lewat atau penuh. Anda dialihkan otomatis ke sesi terdekat berikutnya ({$nextSession->formatted_time_range} WIB).");
            }

            return redirect()->route('schedule')
                ->with('warning', "Sesi {$session->formatted_time_range} WIB sudah tidak dapat dipesan (waktu telah lewat atau slot penuh).");
        }

        // Fetch other upcoming bookable sessions today so user can easily switch without leaving the page
        $otherSessions = GameSession::bookable()
            ->where('date', $session->date->toDateString())
            ->where('id', '!=', $session->id)
            ->orderBy('start_time', 'asc')
            ->take(8)
            ->get();

        return view('booking.show', compact('session', 'otherSessions'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Waitlist;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        // Active upcoming bookings
        $activeBookings = Booking::with(['gameSession.teams', 'bookingPlayers.team'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending_payment'])
            ->whereHas('gameSession', function ($q) {
                $q->where('date', '>=', Carbon::today()->toDateString())
                    ->whereNotIn('status', ['completed', 'cancelled']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Booking History (Completed or Past)
        $historyBookings = Booking::with(['gameSession', 'bookingPlayers.team'])
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereIn('status', ['cancelled', 'expired'])
                    ->orWhereHas('gameSession', function ($sub) {
                        $sub->where('date', '<', Carbon::today()->toDateString())
                            ->orWhere('status', 'completed');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Active Waitlists
        $activeWaitlists = Waitlist::with(['gameSession.teams'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['waiting', 'offered'])
            ->whereHas('gameSession', function ($q) {
                $q->where('date', '>=', Carbon::today()->toDateString());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Statistics
        $totalBookings = Booking::where('user_id', $user->id)->count();
        $confirmedCount = Booking::where('user_id', $user->id)->where('status', 'confirmed')->count();

        return view('dashboard', compact(
            'user',
            'activeBookings',
            'historyBookings',
            'activeWaitlists',
            'totalBookings',
            'confirmedCount'
        ));
    }
}

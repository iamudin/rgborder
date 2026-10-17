<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\Setting;
use App\Services\SessionService;
use App\Services\WaitlistService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request, SessionService $sessionService): View
    {
        $selectedDate = $request->query('date', Carbon::today()->toDateString());

        // Validate date format
        try {
            $parsedDate = Carbon::parse($selectedDate);
        } catch (\Throwable $e) {
            $parsedDate = Carbon::today();
            $selectedDate = $parsedDate->toDateString();
        }

        $sessions = $sessionService->getSessionsForDate($selectedDate);
        $price = Setting::get('price_per_player', 25000);
        $statusOptions = GameSession::getStatusOptions();
        $selectedStatus = $request->query('status', 'all');

        // Status counts for selected date
        $statusCounts = [
            'all' => $sessions->count(),
            'open' => $sessions->filter(fn($s) => $s->isOpen() && !$s->isPastTime())->count(),
            'full' => $sessions->filter(fn($s) => $s->isFull() && !$s->isPastTime())->count(),
            'ready' => $sessions->filter(fn($s) => $s->isReady())->count(),
            'playing' => $sessions->filter(fn($s) => $s->isPlaying())->count(),
            'completed' => $sessions->filter(fn($s) => $s->isCompleted() || ($s->isPastTime() && !$s->isCancelled()))->count(),
            'cancelled' => $sessions->filter(fn($s) => $s->isCancelled())->count(),
        ];

        // Next 7 days for quick tabs
        $dateTabs = [];
        for ($i = 0; $i < 7; $i++) {
            $d = Carbon::today()->addDays($i);
            $dateTabs[] = [
                'date' => $d->toDateString(),
                'day_name' => $i === 0 ? 'Hari Ini' : ($i === 1 ? 'Besok' : $d->locale('id')->isoFormat('dddd')),
                'formatted' => $d->format('d M'),
            ];
        }

        return view('schedule', compact('sessions', 'selectedDate', 'dateTabs', 'price', 'statusOptions', 'statusCounts', 'selectedStatus'));
    }

    public function joinWaitlist(Request $request, GameSession $session, WaitlistService $waitlistService): JsonResponse|RedirectResponse
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan masuk terlebih dahulu untuk bergabung ke daftar tunggu.',
                    'redirect' => route('login', ['redirect_to' => route('schedule', ['date' => $session->date->toDateString()])]),
                ], 401);
            }

            return redirect()->route('login', ['redirect_to' => route('schedule', ['date' => $session->date->toDateString()])])
                ->with('warning', 'Silakan masuk terlebih dahulu untuk bergabung ke daftar tunggu.');
        }

        $validated = $request->validate([
            'player_name' => ['required', 'string', 'max:50'],
            'player_phone' => ['required', 'string', 'max:20'],
            'player_count' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        try {
            $waitlist = $waitlistService->joinWaitlist(
                $session,
                Auth::user(),
                $validated['player_name'],
                $validated['player_phone'],
                (int) $validated['player_count']
            );

            $msg = "Anda berhasil masuk daftar tunggu sesi {$session->formatted_time_range} (Nomor Antrean #{$waitlist->queue_number}). Kami akan memberi tahu jika slot terbuka!";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'queue_number' => $waitlist->queue_number,
                ]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

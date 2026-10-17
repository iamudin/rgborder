<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\GameSession;
use App\Models\User;
use App\Models\Waitlist;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WaitlistService
{
    /**
     * Join waitlist for a full session.
     *
     * @param GameSession $session
     * @param User $user
     * @param string $playerName
     * @param string $playerPhone
     * @param int $playerCount
     * @return Waitlist
     * @throws ValidationException
     */
    public function joinWaitlist(
        GameSession $session,
        User $user,
        string $playerName,
        string $playerPhone,
        int $playerCount = 1
    ): Waitlist {
        // Prevent duplicate active waitlist entry for the same user or phone in the same session
        $existing = Waitlist::where('game_session_id', $session->id)
            ->whereIn('status', ['waiting', 'offered'])
            ->where(function ($q) use ($user, $playerPhone) {
                $q->where('user_id', $user->id)
                    ->orWhere('player_phone', $playerPhone);
            })
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'waitlist' => 'Anda sudah terdaftar dalam antrean sesi ini (Nomor Antrean #'.$existing->queue_number.').',
            ]);
        }

        $nextQueue = (int) (Waitlist::where('game_session_id', $session->id)->max('queue_number') ?? 0) + 1;

        $waitlist = Waitlist::create([
            'game_session_id' => $session->id,
            'user_id' => $user->id,
            'player_name' => $playerName,
            'player_phone' => $playerPhone,
            'player_count' => $playerCount,
            'queue_number' => $nextQueue,
            'status' => 'waiting',
        ]);

        ActivityLog::record('waitlist.joined', $waitlist, [
            'session_code' => $session->session_code,
            'queue' => $nextQueue,
            'players' => $playerCount,
        ], $user);

        return $waitlist;
    }

    /**
     * Check if spots opened up and offer to next eligible waiting candidate.
     *
     * @param GameSession $session
     * @return Waitlist|null
     */
    public function checkAndOfferAvailableSlots(GameSession $session): ?Waitlist
    {
        $session->loadMissing('teams.bookingPlayers.booking');
        $available = $session->available_slots_count;

        if ($available <= 0) {
            return null;
        }

        // Check if there is already an active pending offer
        $activeOffer = Waitlist::where('game_session_id', $session->id)
            ->where('status', 'offered')
            ->where('offer_expires_at', '>', Carbon::now())
            ->first();

        if ($activeOffer) {
            return $activeOffer;
        }

        // Find oldest waiting entry that fits the available slot count
        $candidate = Waitlist::where('game_session_id', $session->id)
            ->where('status', 'waiting')
            ->where('player_count', '<=', $available)
            ->orderBy('queue_number')
            ->first();

        if ($candidate) {
            $candidate->offerSlot(15);

            ActivityLog::record('waitlist.slot_offered', $candidate, [
                'session_code' => $session->session_code,
                'queue' => $candidate->queue_number,
                'expires_at' => $candidate->offer_expires_at->toDateTimeString(),
            ]);

            return $candidate;
        }

        return null;
    }

    /**
     * Expire offers that were not taken within 15 minutes.
     *
     * @return int
     */
    public function expireStaleOffers(): int
    {
        $expiredOffers = Waitlist::where('status', 'offered')
            ->whereNotNull('offer_expires_at')
            ->where('offer_expires_at', '<', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredOffers as $offer) {
            $offer->update(['status' => 'expired']);
            $session = $offer->gameSession;

            ActivityLog::record('waitlist.offer_expired', $offer, [
                'session_code' => $session->session_code,
                'queue' => $offer->queue_number,
            ]);

            // Immediately attempt offering to next candidate in line
            $this->checkAndOfferAvailableSlots($session);
            $count++;
        }

        return $count;
    }
}

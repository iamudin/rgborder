<?php

use App\Services\BookingService;
use App\Services\GameSessionService;
use App\Services\WaitlistService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:expire-holds', function (BookingService $bookingService) {
    $count = $bookingService->expireStaleHolds();
    $this->info("Expired {$count} stale booking holds.");
})->purpose('Expire booking holds that exceeded payment timeout');

Artisan::command('waitlists:expire-offers', function (WaitlistService $waitlistService) {
    $count = $waitlistService->expireStaleOffers();
    $this->info("Expired {$count} unaccepted waitlist offers.");
})->purpose('Expire unaccepted waitlist offers');

Artisan::command('sessions:check-timers', function (GameSessionService $gameSessionService) {
    $count = $gameSessionService->checkAndAutoCompletePlayingSessions();
    $this->info("Auto-completed {$count} finished playing sessions.");
})->purpose('Check and auto-complete sessions where the game timer has elapsed');

// Schedule commands to run every minute
Schedule::command('bookings:expire-holds')->everyMinute();
Schedule::command('waitlists:expire-offers')->everyMinute();
Schedule::command('sessions:check-timers')->everyMinute();

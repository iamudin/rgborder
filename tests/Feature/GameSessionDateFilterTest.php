<?php

use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Models\GameSession;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed');
});

test('game sessions table can be filtered by specific date', function () {
    $admin = User::where('role', 'admin')->first();

    $targetDate = '2026-11-20';
    $otherDate = '2026-11-25';

    $sessionA = GameSession::first();
    $sessionA->update(['date' => $targetDate]);

    $sessionB = GameSession::skip(1)->first();
    $sessionB->update(['date' => $otherDate]);

    Livewire::actingAs($admin)
        ->test(ListGameSessions::class)
        ->assertSuccessful()
        ->filterTable('date', ['date' => $targetDate])
        ->assertCanSeeTableRecords([$sessionA])
        ->assertCanNotSeeTableRecords([$sessionB]);
});

test('game sessions table can be filtered by date range', function () {
    $admin = User::where('role', 'admin')->first();

    $sessionA = GameSession::first();
    $sessionA->update(['date' => '2026-11-10']);

    $sessionB = GameSession::skip(1)->first();
    $sessionB->update(['date' => '2026-11-15']);

    $sessionC = GameSession::skip(2)->first();
    $sessionC->update(['date' => '2026-11-25']);

    Livewire::actingAs($admin)
        ->test(ListGameSessions::class)
        ->assertSuccessful()
        ->filterTable('date', [
            'date_from' => '2026-11-12',
            'date_until' => '2026-11-20',
        ])
        ->assertCanSeeTableRecords([$sessionB])
        ->assertCanNotSeeTableRecords([$sessionA, $sessionC]);
});

test('game sessions list page has date tabs for today and tomorrow', function () {
    $admin = User::where('role', 'admin')->first();

    $today = Carbon::today()->toDateString();
    $tomorrow = Carbon::tomorrow()->toDateString();

    $todaySession = GameSession::first();
    $todaySession->update(['date' => $today]);

    $tomorrowSession = GameSession::skip(1)->first();
    $tomorrowSession->update(['date' => $tomorrow]);

    Livewire::actingAs($admin)
        ->test(ListGameSessions::class)
        ->assertSuccessful()
        ->set('activeTab', 'today')
        ->assertCanSeeTableRecords([$todaySession])
        ->assertCanNotSeeTableRecords([$tomorrowSession])
        ->set('activeTab', 'tomorrow')
        ->assertCanSeeTableRecords([$tomorrowSession])
        ->assertCanNotSeeTableRecords([$todaySession]);
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyBookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - RGB Arena Customer Frontend
|--------------------------------------------------------------------------
*/

// Public Frontend Pages
Route::get('/', HomeController::class)->name('home');
Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule');
Route::post('/jadwal/{session}/waitlist', [ScheduleController::class, 'joinWaitlist'])->name('schedule.waitlist');

// Booking Flow
Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
Route::get('/booking/{session}', [BookingController::class, 'show'])->name('booking.show');

// Check-In & Digital Ticket Verification
Route::get('/check-in/{token}', [\App\Http\Controllers\CheckInController::class, 'show'])->name('check-in.show');
Route::post('/check-in/{token}', [\App\Http\Controllers\CheckInController::class, 'confirm'])->name('check-in.confirm');
Route::get('/tiket/{token}', fn(string $token) => redirect()->route('check-in.show', $token));

// Informational & Content Pages (About, Rules, Facilities, etc.)
Route::get('/info/{slug}', [\App\Http\Controllers\ContentPageController::class, 'show'])->name('pages.show');
Route::get('/about', fn() => redirect()->route('pages.show', 'about'));
Route::get('/tentang', fn() => redirect()->route('pages.show', 'about'));
Route::get('/rules', fn() => redirect()->route('pages.show', 'rules'));
Route::get('/aturan', fn() => redirect()->route('pages.show', 'rules'));

// Authentication Routes (Google OAuth + Dev Login)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/auth/dev-login', [AuthController::class, 'devLogin'])->name('auth.dev-login');

// Authenticated Customer Area
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/booking-saya', [MyBookingController::class, 'index'])->name('my-bookings');
    Route::get('/booking-saya/{code}', [MyBookingController::class, 'show'])->name('my-bookings.show');
    Route::post('/booking-saya/{code}/upload-proof', [MyBookingController::class, 'uploadPaymentProof'])->name('my-bookings.upload-proof');
    Route::post('/booking-saya/{code}/cancel', [MyBookingController::class, 'cancel'])->name('my-bookings.cancel');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');
});

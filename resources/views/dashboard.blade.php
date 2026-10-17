@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Dashboard Pelanggan</span>
            <h1 class="font-orbitron font-extrabold text-2xl sm:text-4xl text-white tracking-wide mt-1">
                Halo, <span class="text-cyan-400">{{ $user->name }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Kelola tiket aktif, pantau status antrean, dan pantau riwayat pertempuran arena Anda.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('schedule') }}" wire:navigate.hover class="px-5 py-2.5 rounded-xl text-xs font-orbitron font-bold uppercase tracking-wider text-black bg-cyan-400 hover:bg-cyan-300 transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                + Booking Sesi Baru
            </a>
        </div>
    </div>

    <!-- Active Waitlist Offers Alert (If Any) -->
    @foreach($activeWaitlists as $waitlist)
        @if($waitlist->isOffered())
            <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-950/90 to-purple-950/90 border border-amber-400 shadow-[0_0_30px_rgba(245,158,11,0.25)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="text-3xl animate-bounce">⚡</span>
                    <div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-400 text-black">PENAWARAN SLOT TERBUKA!</span>
                        <h3 class="font-orbitron font-bold text-lg text-white mt-1">
                            Slot Tersedia untuk Sesi {{ $waitlist->gameSession->formatted_time_range }} WIB
                        </h3>
                        <p class="text-xs text-amber-200 mt-0.5">
                            Segera lakukan booking sebelum penawaran berakhir pada <strong>{{ $waitlist->offer_expires_at->format('H:i') }} WIB</strong>.
                        </p>
                    </div>
                </div>

                <a href="{{ route('booking.show', $waitlist->gameSession) }}" wire:navigate.hover class="px-6 py-3 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-amber-400 hover:bg-amber-300 text-center flex-shrink-0">
                    Klaim Slot Sekarang →
                </a>
            </div>
        @endif
    @endforeach

    <!-- Statistics Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-lg">
            <span class="text-xs text-slate-400 font-mono">TOTAL BOOKING</span>
            <div class="font-orbitron font-black text-3xl text-white mt-2">{{ $totalBookings }}</div>
            <span class="text-[11px] text-slate-500 mt-1 block">Seluruh riwayat pesanan</span>
        </div>

        <div class="p-5 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-lg">
            <span class="text-xs text-emerald-400 font-mono">BOOKING TERKONFIRMASI</span>
            <div class="font-orbitron font-black text-3xl text-emerald-400 mt-2">{{ $confirmedCount }}</div>
            <span class="text-[11px] text-slate-500 mt-1 block">Tiket sah siap bertempur</span>
        </div>

        <div class="p-5 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-lg">
            <span class="text-xs text-amber-400 font-mono">ANTREAN AKTIF</span>
            <div class="font-orbitron font-black text-3xl text-amber-400 mt-2">{{ $activeWaitlists->count() }}</div>
            <span class="text-[11px] text-slate-500 mt-1 block">Daftar tunggu sesi penuh</span>
        </div>
    </div>

    <!-- Active Upcoming Bookings -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-orbitron font-bold text-xl text-white tracking-wide">Jadwal Sesi Mendatang</h2>
            <a href="{{ route('my-bookings') }}" wire:navigate.hover class="text-xs text-cyan-400 hover:text-cyan-300">Lihat Semua →</a>
        </div>

        @if($activeBookings->isEmpty())
            <div class="p-8 rounded-2xl bg-slate-900/50 border border-slate-800 text-center">
                <p class="text-xs text-slate-400">Tidak ada sesi aktif saat ini. Siap bertempur? Pilih jadwal dan pesan tiket sekarang.</p>
                <a href="{{ route('schedule') }}" wire:navigate.hover class="inline-block mt-3 px-4 py-2 rounded-lg text-xs font-bold text-black bg-cyan-400 hover:bg-cyan-300 uppercase">
                    Pesan Tiket Arena
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($activeBookings as $booking)
                    @php
                        $session = $booking->gameSession;
                        $team = $booking->targetTeam;
                        $isRed = $team?->color === 'red';
                    @endphp
                    <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 hover:border-cyan-500/40 transition-all flex flex-col justify-between space-y-4 shadow-xl">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="font-mono text-xs text-cyan-400 font-bold">{{ $booking->booking_code }}</span>
                                @if($booking->isConfirmed())
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950 text-emerald-300 border border-emerald-500/30">SIAP MAIN</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-950 text-amber-300 border border-amber-500/30">PENDING BAYAR</span>
                                @endif
                            </div>

                            <h3 class="font-orbitron font-extrabold text-2xl text-white">
                                {{ $session->formatted_time_range }} WIB
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $session->date->isoFormat('dddd, D MMMM Y') }}</p>

                            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                                <span class="text-slate-400">Tim:</span>
                                <span class="font-bold {{ $isRed ? 'text-red-400' : 'text-blue-400' }}">
                                    {{ $team?->name ?? 'Ditentukan Sistem' }}
                                </span>
                            </div>

                            <div class="mt-1 flex items-center justify-between text-xs">
                                <span class="text-slate-400">Total Pemain:</span>
                                <span class="font-bold text-white">{{ $booking->total_players }} Orang</span>
                            </div>
                        </div>

                        <div>
                            <a href="{{ route('my-bookings.show', $booking->booking_code) }}" wire:navigate.hover class="w-full py-2.5 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-cyan-400 hover:bg-cyan-300 transition-all text-center block shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                                Lihat Tiket QR →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection

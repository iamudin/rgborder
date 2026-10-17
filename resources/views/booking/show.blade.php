@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Breadcrumb & Header -->
    <div class="mb-6">
        <a href="{{ route('schedule', ['date' => $session->date->toDateString()]) }}" wire:navigate.hover class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-cyan-400 transition-colors mb-3">
            <span>← Kembali ke Daftar Jadwal</span>
        </a>

        @if(session('warning'))
            <div class="mb-5 p-4 rounded-xl bg-amber-950/80 border border-amber-500/50 text-amber-200 flex items-start gap-3 shadow-[0_0_20px_rgba(245,158,11,0.2)]">
                <span class="text-xl">⚠️</span>
                <div>
                    <p class="font-bold text-sm">Penyesuaian Sesi Otomatis</p>
                    <p class="text-xs text-amber-300 mt-0.5">{{ session('warning') }}</p>
                </div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Sistem Booking Arena</span>
                <h1 class="font-orbitron font-extrabold text-2xl sm:text-3xl text-white tracking-wide mt-1">
                    Booking Sesi <span class="text-cyan-400">{{ $session->formatted_time_range }} WIB</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    {{ $session->date->isoFormat('dddd, D MMMM Y') }} &bull; Durasi {{ $session->duration_minutes }} Menit &bull; Maksimal {{ $session->max_players }} Pemain
                </p>
            </div>

            <!-- Capacity Badges -->
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-xs font-medium text-slate-300">
                    Slot Tersedia: <strong class="text-emerald-400">{{ $session->available_slots_count }}/{{ $session->max_players }}</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Quick Upcoming Session Switcher (Ignoring Passed Hours) -->
    @if(isset($otherSessions) && $otherSessions->isNotEmpty())
        <div class="mb-8 p-4 rounded-2xl bg-[#0f172a]/80 border border-slate-800 shadow-lg">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-300 font-bold">Pilih Sesi Terdekat Hari Ini:</span>
                </div>
                <span class="text-[11px] text-slate-400 font-mono">Waktu yang sudah lewat diabaikan</span>
            </div>

            <div class="flex flex-wrap gap-2">
                <!-- Current Session (Selected) -->
                <span class="px-3.5 py-2 rounded-xl bg-cyan-500/20 border border-cyan-400 text-cyan-300 font-mono font-bold text-xs shadow-[0_0_15px_rgba(6,182,212,0.35)] flex items-center gap-2">
                    <span>✓</span>
                    <span>{{ $session->formatted_time_range }} WIB</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-cyan-950 text-cyan-200 border border-cyan-500/40">Terpilih</span>
                </span>

                <!-- Other Upcoming Bookable Sessions -->
                @foreach($otherSessions as $other)
                    <a href="{{ route('booking.show', $other) }}" wire:navigate.hover class="px-3.5 py-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 border border-slate-700 hover:border-cyan-500/60 text-slate-300 hover:text-white font-mono text-xs transition-all flex items-center gap-2 group">
                        <span>{{ $other->formatted_time_range }} WIB</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-950 text-emerald-400 font-semibold group-hover:bg-emerald-950/60 border border-slate-800">{{ $other->available_slots_count }} slot</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Livewire Booking Form -->
    <livewire:booking-form :session="$session" />
</div>
@endsection

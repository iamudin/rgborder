@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Riwayat Tiket & Pesanan</span>
            <h1 class="font-orbitron font-extrabold text-2xl sm:text-4xl text-white tracking-wide mt-1">
                Booking Saya
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Daftar tiket aktif, status verifikasi pembayaran, dan riwayat pertempuran Anda.
            </p>
        </div>

        <a href="{{ route('schedule') }}" wire:navigate.hover class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-orbitron font-bold uppercase tracking-wider text-black bg-cyan-400 hover:bg-cyan-300 transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)]">
            + Pesan Sesi Baru
        </a>
    </div>

    <div class="space-y-4">
        @forelse($bookings as $booking)
            @php
                $session = $booking->gameSession;
                $isPending = $booking->isPendingPayment();
                $isConfirmed = $booking->isConfirmed();
                $isExpired = $booking->isExpired();
                $isCancelled = $booking->isCancelled();
            @endphp
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 hover:border-slate-700 transition-all flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="font-orbitron font-bold text-base text-cyan-400 tracking-wider">{{ $booking->booking_code }}</span>
                        
                        @if($isConfirmed)
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950/80 border border-emerald-500/50 text-emerald-300">TERKONFIRMASI</span>
                        @elseif($isPending)
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-950/80 border border-amber-500/50 text-amber-300 animate-pulse">MENUNGGU PEMBAYARAN</span>
                        @elseif($isExpired)
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 border border-slate-700 text-slate-400">KEDALUWARSA</span>
                        @elseif($isCancelled)
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-red-950/80 border border-red-500/50 text-red-300">DIBATALKAN</span>
                        @endif

                        <span class="text-xs text-slate-400 font-medium">&bull; {{ $booking->formatted_booking_type }}</span>
                    </div>

                    <div>
                        <h3 class="font-orbitron font-bold text-lg text-white">
                            {{ $session->formatted_time_range }} WIB
                            <span class="text-xs font-normal text-slate-400 ml-1">({{ $session->date->isoFormat('dddd, D MMMM Y') }})</span>
                        </h3>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-300">
                        <span>Tim: <strong>{{ $booking->targetTeam?->name ?? 'Ditentukan Sistem' }}</strong></span>
                        <span>Total Tagihan: <strong class="text-cyan-400">Rp{{ number_format($booking->total_amount, 0, ',', '.') }}</strong></span>
                        <span>Pemain: <strong>{{ $booking->bookingPlayers->pluck('player_name')->join(', ') }}</strong></span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('my-bookings.show', $booking->booking_code) }}" wire:navigate.hover class="px-5 py-2.5 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-slate-100 bg-slate-800 hover:bg-slate-700 border border-slate-600 transition-all">
                        Detail & Tiket QR →
                    </a>
                </div>
            </div>
        @empty
            <div class="p-12 rounded-2xl bg-slate-900/50 border border-slate-800 text-center">
                <span class="text-3xl block mb-2">🎟️</span>
                <h4 class="font-orbitron font-bold text-white text-base">Belum Ada Riwayat Booking</h4>
                <p class="text-xs text-slate-400 mt-1">Anda belum melakukan pemesanan tiket arena.</p>
                <a href="{{ route('schedule') }}" wire:navigate.hover class="inline-block mt-4 px-4 py-2 rounded-lg text-xs font-bold text-black bg-cyan-400 hover:bg-cyan-300 uppercase">
                    Lihat Jadwal & Pesan Tiket
                </a>
            </div>
        @endforelse

        <div class="pt-4">
            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection

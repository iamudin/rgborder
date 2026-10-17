@extends('layouts.app', ['title' => 'Tiket Digital & Check-In Pemain — RGB Arena'])

@section('content')
<div class="relative max-w-xl mx-auto px-4 py-8 sm:py-12 z-10">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-950/90 border border-emerald-500 shadow-[0_0_25px_rgba(16,185,129,0.4)] flex items-start gap-3 text-emerald-200 animate-pulse">
            <span class="text-2xl">🎉</span>
            <div>
                <h4 class="font-orbitron font-bold text-sm text-emerald-300">BERHASIL CHECK-IN!</h4>
                <p class="text-xs text-emerald-100 mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-red-950/90 border border-red-500 shadow-[0_0_25px_rgba(239,68,68,0.4)] flex items-start gap-3 text-red-200">
            <span class="text-2xl">⚠️</span>
            <div>
                <h4 class="font-orbitron font-bold text-sm text-red-300">GAGAL VERIFIKASI</h4>
                <p class="text-xs text-red-100 mt-0.5">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if($player)
        @php
            $booking = $player->booking;
            $session = $booking->gameSession;
            $team = $player->team;
            $isRed = $team && str_contains(strtolower($team->name), 'merah');
            $isChecked = $player->isCheckedIn();
        @endphp

        <!-- DIGITAL TICKET CARD -->
        <div class="rounded-3xl bg-[#0b1222]/95 border {{ $isChecked ? 'border-emerald-500/60 shadow-[0_0_40px_rgba(16,185,129,0.25)]' : 'border-cyan-500/40 shadow-[0_0_40px_rgba(6,182,212,0.2)]' }} p-6 sm:p-8 relative overflow-hidden backdrop-blur-xl">

            <!-- Cyber Corner Accents -->
            <div class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 {{ $isChecked ? 'border-emerald-400' : 'border-cyan-400' }}"></div>
            <div class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 {{ $isChecked ? 'border-emerald-400' : 'border-cyan-400' }}"></div>
            <div class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 {{ $isChecked ? 'border-emerald-400' : 'border-cyan-400' }}"></div>
            <div class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 {{ $isChecked ? 'border-emerald-400' : 'border-cyan-400' }}"></div>

            <!-- Header Badge & Status -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $isChecked ? 'bg-emerald-400' : 'bg-cyan-400 animate-ping' }}"></span>
                    <span class="font-mono text-xs font-bold uppercase tracking-widest text-cyan-400">
                        RGB ARENA DIGITAL PASS
                    </span>
                </div>
                <span class="px-3 py-1 rounded-full text-[10px] font-mono font-bold tracking-wider uppercase {{ $isChecked ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/50' : 'bg-amber-950 text-amber-300 border border-amber-500/50' }}">
                    {{ $isChecked ? '✓ SUDAH CHECK-IN' : 'BELUM CHECK-IN' }}
                </span>
            </div>

            <!-- Player Profile & Callsign -->
            <div class="py-5 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[11px] font-mono text-slate-400 mb-2">
                    <span>CALLSIGN:</span>
                    <span class="font-bold text-white">{{ $player->playerProfile?->callsign ?? $player->player_name }}</span>
                </div>
                <h1 class="font-orbitron font-extrabold text-2xl sm:text-3xl text-white tracking-wide">
                    {{ $player->player_name }}
                </h1>
                <div class="mt-2 inline-block px-4 py-1.5 rounded-xl text-xs font-orbitron font-bold {{ $isRed ? 'bg-red-950/80 text-red-400 border border-red-500/50 shadow-[0_0_15px_rgba(239,68,68,0.3)]' : 'bg-blue-950/80 text-blue-400 border border-blue-500/50 shadow-[0_0_15px_rgba(59,130,246,0.3)]' }}">
                    {{ $team?->name ?? 'Tim Menunggu' }}
                </div>
            </div>

            <!-- Center QR Code Viewport -->
            <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-col items-center justify-center my-4">
                <div class="w-48 h-48 bg-white p-3 rounded-2xl shadow-[0_0_30px_rgba(255,255,255,0.15)] flex items-center justify-center">
                    @if($qrSvg)
                        <div class="w-full h-full">
                            {!! $qrSvg !!}
                        </div>
                    @else
                        <div class="font-mono text-xs text-black text-center">
                            {{ $player->check_in_token }}
                        </div>
                    @endif
                </div>

                <div class="mt-3 text-center">
                    <span class="text-[11px] font-mono text-slate-400 block">Token Tiket:</span>
                    <span class="font-mono font-bold text-sm text-cyan-300 tracking-wider">
                        {{ $player->check_in_token }}
                    </span>
                </div>
            </div>

            <!-- Session & Match Information -->
            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2.5 text-xs font-mono mb-6">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Sesi Permainan:</span>
                    <span class="font-bold text-white">{{ $session->session_name }} ({{ $session->session_code }})</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Jadwal Tanding:</span>
                    <span class="font-bold text-cyan-300">{{ $session->formatted_time_range }} WIB</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Tanggal:</span>
                    <span class="text-slate-200">{{ $session->date->format('d F Y') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Kode Booking:</span>
                    <span class="font-bold text-slate-200">{{ $booking->booking_code }}</span>
                </div>
                @if($isChecked)
                    <div class="flex items-center justify-between pt-2 border-t border-slate-800/80">
                        <span class="text-emerald-400">Waktu Check-In:</span>
                        <span class="font-bold text-emerald-400">{{ $player->checked_in_at->format('H:i:s') }} WIB</span>
                    </div>
                @endif
            </div>

            <!-- ACTION BUTTONS: STAFF VS PLAYER -->
            @if($isAdmin)
                <div class="space-y-3">
                    @if(!$isChecked)
                        <form action="{{ route('check-in.confirm', $player->check_in_token) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 text-black font-orbitron font-extrabold text-sm sm:text-base uppercase tracking-wider hover:from-emerald-300 hover:to-cyan-300 transition-all shadow-[0_0_25px_rgba(16,185,129,0.5)] cursor-pointer flex items-center justify-center gap-2">
                                <span>⚡</span>
                                <span>KONFIRMASI CHECK-IN PEMAIN</span>
                            </button>
                        </form>
                    @else
                        <div class="p-3.5 rounded-2xl bg-emerald-950/70 border border-emerald-500/60 text-center">
                            <span class="text-emerald-300 font-bold text-xs font-mono">
                                ✓ Pemain telah terverifikasi hadir di arena
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2 text-xs">
                        <a href="{{ route('filament.admin.pages.check-in-scanner') }}" 
                           class="text-cyan-400 hover:text-cyan-300 font-mono flex items-center gap-1">
                            <span>📷 Buka Kamera Scanner Admin</span>
                            <span>&rarr;</span>
                        </a>
                        <a href="{{ url('/admin/game-sessions/' . $session->id . '/detail') }}" 
                           class="text-slate-400 hover:text-white font-mono">
                            Ruang Kontrol Sesi &rarr;
                        </a>
                    </div>
                </div>
            @else
                <div class="text-center space-y-3">
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Perlihatkan tiket digital ini di meja resepsionis RGB Arena atau lakukan scan pada kamera check-in di lokasi saat tiba.
                    </p>
                    @if(!Auth::check())
                        <div class="pt-2 border-t border-slate-800">
                            <a href="{{ route('login') }}" class="text-[11px] text-cyan-400 hover:underline font-mono">
                                Login sebagai Staf Arena untuk verifikasi tiket &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            @endif

        </div>

    @else
        <!-- NOT FOUND STATE -->
        <div class="rounded-3xl bg-[#0b1222]/95 border border-red-500/40 p-8 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-red-950/60 border border-red-500/40 mx-auto flex items-center justify-center text-3xl text-red-400">
                ✕
            </div>
            <h2 class="font-orbitron font-extrabold text-xl text-white">Tiket QR Tidak Ditemukan</h2>
            <p class="text-xs text-slate-400 max-w-md mx-auto">
                Kode tiket QR <span class="font-mono text-red-400">{{ $token }}</span> tidak terdaftar dalam sistem atau sudah kedaluwarsa.
            </p>
            <div class="pt-4 flex justify-center gap-3">
                <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-white hover:bg-slate-800">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    @endif

</div>
@endsection

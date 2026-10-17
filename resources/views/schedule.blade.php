@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Jadwal Operasional Arena</span>
            <h1 class="font-orbitron font-extrabold text-2xl sm:text-4xl text-white tracking-wide mt-1">
                Jadwal Permainan & Slot Sesi
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Pilih tanggal bermain untuk melihat status ketersediaan slot tim merah dan biru.
            </p>
        </div>

        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300 flex items-center gap-3">
            <span class="text-cyan-400 font-bold">Harga Tiket:</span>
            <span class="font-orbitron font-extrabold text-white">Rp{{ number_format($price, 0, ',', '.') }}</span>
            <span class="text-slate-400">/ 30 Menit</span>
        </div>
    </div>

    <!-- Date Navigation Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        @foreach($dateTabs as $tab)
            <a href="{{ route('schedule', ['date' => $tab['date']]) }}" wire:navigate.hover 
               class="flex-shrink-0 px-4 py-2.5 rounded-xl text-center border transition-all {{ $selectedDate === $tab['date'] ? 'bg-cyan-950/80 border-cyan-400 text-cyan-300 shadow-[0_0_15px_rgba(6,182,212,0.3)]' : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:text-white hover:border-slate-700' }}">
                <div class="text-[10px] font-bold uppercase tracking-wider">{{ $tab['day_name'] }}</div>
                <div class="font-orbitron font-bold text-sm mt-0.5 text-white">{{ $tab['formatted'] }}</div>
            </a>
        @endforeach
    </div>

    <!-- Sessions List & Interactive Status Filter -->
    <div x-data="{
        activeStatus: '{{ $selectedStatus }}',
        totalSessions: {{ $sessions->count() }},
        get visibleCount() {
            if (this.activeStatus === 'all') return this.totalSessions;
            const elements = this.$el.querySelectorAll('[data-status=\'' + this.activeStatus + '\']');
            return elements.length;
        }
    }" class="space-y-6">

        <!-- Status Filter Pills (Matching public GameSession Status List: Open, Full, Ready, Playing, Completed, Cancelled) -->
        <div class="p-3 rounded-2xl bg-[#0f172a]/80 border border-slate-800/80 shadow-lg">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2.5">
                <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                    <span>Filter Status Sesi Pertempuran:</span>
                </span>
                <span class="text-[11px] text-slate-400 font-mono">
                    Total: {{ $statusCounts['all'] }} Sesi Terjadwal
                </span>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs">
                <!-- All -->
                <button type="button" @click="activeStatus = 'all'"
                        :class="activeStatus === 'all' ? 'bg-cyan-500/20 text-cyan-300 border-cyan-500/60 shadow-[0_0_12px_rgba(6,182,212,0.3)]' : 'bg-slate-900/70 text-slate-400 border-slate-800 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl border font-mono font-medium transition-all flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <span>Semua Status</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-950 font-bold text-slate-300">{{ $statusCounts['all'] }}</span>
                </button>

                <!-- Open (Slot Terbuka) -->
                <button type="button" @click="activeStatus = 'open'"
                        :class="activeStatus === 'open' ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.3)]' : 'bg-slate-900/70 text-slate-400 border-slate-800 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl border font-mono font-medium transition-all flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Open (Terbuka)</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-950 font-bold text-emerald-400">{{ $statusCounts['open'] }}</span>
                </button>

                <!-- Full (Penuh) -->
                <button type="button" @click="activeStatus = 'full'"
                        :class="activeStatus === 'full' ? 'bg-red-950/80 text-red-300 border-red-500 shadow-[0_0_12px_rgba(239,68,68,0.3)]' : 'bg-slate-900/70 text-slate-400 border-slate-800 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl border font-mono font-medium transition-all flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span>Full (Penuh)</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-950 font-bold text-red-400">{{ $statusCounts['full'] }}</span>
                </button>

                <!-- Ready (Siap) -->
                <button type="button" @click="activeStatus = 'ready'"
                        :class="activeStatus === 'ready' ? 'bg-blue-950/80 text-blue-300 border-blue-500 shadow-[0_0_12px_rgba(59,130,246,0.3)]' : 'bg-slate-900/70 text-slate-400 border-slate-800 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl border font-mono font-medium transition-all flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    <span>Ready (Siap)</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-950 font-bold text-blue-400">{{ $statusCounts['ready'] }}</span>
                </button>

                <!-- Playing (Berlangsung) -->
                <button type="button" @click="activeStatus = 'playing'"
                        :class="activeStatus === 'playing' ? 'bg-amber-950/80 text-amber-300 border-amber-500 shadow-[0_0_12px_rgba(245,158,11,0.3)]' : 'bg-slate-900/70 text-slate-400 border-slate-800 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl border font-mono font-medium transition-all flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Playing (Berlangsung)</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-950 font-bold text-amber-400">{{ $statusCounts['playing'] }}</span>
                </button>

                <!-- Completed (Selesai) -->
                <button type="button" @click="activeStatus = 'completed'"
                        :class="activeStatus === 'completed' ? 'bg-slate-800 text-slate-200 border-slate-600' : 'bg-slate-900/70 text-slate-400 border-slate-800 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl border font-mono font-medium transition-all flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <span>Completed (Selesai)</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-950 font-bold text-slate-400">{{ $statusCounts['completed'] }}</span>
                </button>

                <!-- Cancelled (Batal) -->
                <button type="button" @click="activeStatus = 'cancelled'"
                        :class="activeStatus === 'cancelled' ? 'bg-red-950/80 text-red-300 border-red-500' : 'bg-slate-900/70 text-slate-400 border-slate-800 hover:text-white'"
                        class="px-3.5 py-1.5 rounded-xl border font-mono font-medium transition-all flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <span>Cancelled (Batal)</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-950 font-bold text-red-400">{{ $statusCounts['cancelled'] }}</span>
                </button>
            </div>
        </div>

        <!-- Sessions List -->
        <div class="space-y-4">
            @forelse($sessions as $session)
                @php
                    $isDraft = $session->isDraft();
                    $isCancelled = $session->isCancelled();
                    $isPlaying = $session->isPlaying();
                    $isCompleted = $session->isCompleted();
                    $isReady = $session->isReady();
                    $isPassed = $session->isPastTime();
                    $available = $session->available_slots_count;
                    $isFull = $session->isFull();
                    $red = $session->redTeam;
                    $blue = $session->blueTeam;
                    $percent = round((($session->max_players - $available) / $session->max_players) * 100);

                    // Effective status for filtering matching the exact 7 status options
                    $effectiveStatus = match (true) {
                        $isCancelled => 'cancelled',
                        $isDraft => 'draft',
                        $isPlaying => 'playing',
                        $isCompleted => 'completed',
                        $isReady => 'ready',
                        $isPassed => 'completed',
                        $isFull => 'full',
                        default => 'open',
                    };

                    $cardStyle = match ($effectiveStatus) {
                        'cancelled' => 'border-red-950/70 bg-slate-950/70 opacity-70',
                        'draft' => 'border-slate-800 bg-slate-950/60 opacity-80',
                        'playing' => 'border-amber-500/50 bg-[#0f172a]/95 shadow-[0_0_20px_rgba(245,158,11,0.15)]',
                        'ready' => 'border-blue-500/50 bg-[#0f172a]/95 shadow-[0_0_20px_rgba(59,130,246,0.15)]',
                        'completed' => 'border-slate-800/80 bg-slate-950/60 opacity-85',
                        'full' => 'border-red-900/40 bg-[#0f172a]/90',
                        default => 'border-slate-700/80 hover:border-cyan-500/50 bg-[#0f172a]/90',
                    };
                @endphp
                <div x-show="activeStatus === 'all' || activeStatus === '{{ $effectiveStatus }}'"
                     data-status="{{ $effectiveStatus }}"
                     class="p-5 sm:p-6 rounded-2xl border {{ $cardStyle }} transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-6 shadow-xl">
                    
                    <!-- Sesi Details Left -->
                    <div class="flex-1 space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-slate-400 font-semibold">{{ $session->session_code }}</span>
                            
                            <!-- Badges aligned with all 7 statuses from Filament admin -->
                            @if($isCancelled)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-red-950/90 border border-red-500/60 text-red-400">CANCELLED (BATAL)</span>
                            @elseif($isDraft)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 border border-slate-700 text-slate-400">DRAFT (PERSIAPAN)</span>
                            @elseif($isPlaying)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-950/80 border border-amber-500/50 text-amber-300 animate-pulse">PLAYING (BERLANGSUNG)</span>
                            @elseif($isCompleted)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 border border-slate-700 text-slate-400">COMPLETED (SELESAI)</span>
                            @elseif($isReady)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-950/80 border border-blue-500/50 text-blue-300 animate-pulse">READY (SIAP TEMPUR)</span>
                            @elseif($isPassed)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800/80 border border-slate-700 text-slate-500">WAKTU TELAH LEWAT</span>
                            @elseif($isFull)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-red-950/80 border border-red-500/50 text-red-300">FULL (PENUH)</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950/80 border border-emerald-500/50 text-emerald-300">OPEN (SLOT TERBUKA)</span>
                            @endif

                            <span class="text-xs text-slate-400 font-medium">Durasi: {{ $session->duration_minutes }} Menit</span>
                        </div>

                        <div>
                            <h3 class="font-orbitron font-extrabold text-2xl sm:text-3xl text-white tracking-wide">
                                {{ $session->formatted_time_range }} <span class="text-xs font-normal text-slate-400">WIB</span>
                            </h3>
                        </div>

                        <!-- Rosters Breakdown -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-lg">
                            <!-- Tim Merah -->
                            <div class="p-3 rounded-xl bg-red-950/20 border border-red-900/40">
                                <div class="flex items-center justify-between text-xs font-bold text-red-400 mb-1.5">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span>Tim Merah</span>
                                    </span>
                                    <span>{{ $red?->occupied_slots_count ?? 0 }}/{{ $red?->max_players ?? 4 }} Pemain</span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ (($red?->occupied_slots_count ?? 0) / ($red?->max_players ?? 4)) * 100 }}%"></div>
                                </div>
                            </div>

                            <!-- Tim Biru -->
                            <div class="p-3 rounded-xl bg-blue-950/20 border border-blue-900/40">
                                <div class="flex items-center justify-between text-xs font-bold text-blue-400 mb-1.5">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span>Tim Biru</span>
                                    </span>
                                    <span>{{ $blue?->occupied_slots_count ?? 0 }}/{{ $blue?->max_players ?? 4 }} Pemain</span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ (($blue?->occupied_slots_count ?? 0) / ($blue?->max_players ?? 4)) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status & Action Right -->
                    <div class="flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end justify-between gap-4 pt-4 lg:pt-0 border-t lg:border-t-0 border-slate-800">
                        <div class="text-left sm:text-right">
                            <div class="text-xs text-slate-400">Total Ketersediaan Sesi</div>
                            @if($isCancelled)
                                <div class="font-orbitron font-bold text-sm text-red-400">Sesi Dibatalkan</div>
                            @elseif($isDraft)
                                <div class="font-orbitron font-bold text-sm text-slate-400">Jadwal Belum Dibuka</div>
                            @elseif($isPlaying)
                                <div class="font-orbitron font-bold text-sm text-amber-400">Pertempuran Aktif</div>
                            @elseif($isCompleted)
                                <div class="font-orbitron font-bold text-sm text-slate-400">Selesai ({{ $session->occupied_slots_count }}/{{ $session->max_players }})</div>
                            @elseif($isReady)
                                <div class="font-orbitron font-bold text-sm text-blue-400">Pemain Siap Tempur</div>
                            @elseif($isPassed)
                                <div class="font-orbitron font-bold text-sm text-slate-500">Waktu Telah Lewat</div>
                            @else
                                <div class="font-orbitron font-extrabold text-lg {{ $available > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                    {{ $available }} Slot Tersedia <span class="text-xs text-slate-400">({{ $session->occupied_slots_count }}/{{ $session->max_players }})</span>
                                </div>
                            @endif
                        </div>

                        <div>
                            @if($isCancelled)
                                <button disabled class="px-6 py-3 rounded-xl font-orbitron font-semibold text-xs text-red-400/80 bg-red-950/30 border border-red-900/50 cursor-not-allowed">
                                    Sesi Dibatalkan
                                </button>
                            @elseif($isDraft)
                                <button disabled class="px-6 py-3 rounded-xl font-orbitron font-semibold text-xs text-slate-400 bg-slate-900 border border-slate-800 cursor-not-allowed">
                                    Belum Dibuka (Draft)
                                </button>
                            @elseif($isPlaying)
                                <button disabled class="px-6 py-3 rounded-xl font-orbitron font-semibold text-xs text-amber-400 bg-amber-950/40 border border-amber-800 cursor-not-allowed">
                                    Pertempuran Berlangsung
                                </button>
                            @elseif($isCompleted)
                                <button disabled class="px-6 py-3 rounded-xl font-orbitron font-semibold text-xs text-slate-400 bg-slate-900 border border-slate-800 cursor-not-allowed">
                                    Sesi Berakhir
                                </button>
                            @elseif($isReady)
                                <button disabled class="px-6 py-3 rounded-xl font-orbitron font-semibold text-xs text-blue-400 bg-blue-950/40 border border-blue-800/60 cursor-not-allowed flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping"></span>
                                    <span>Pemain Siap / Briefing</span>
                                </button>
                            @elseif($isPassed)
                                <button disabled class="px-6 py-3 rounded-xl font-orbitron font-semibold text-xs text-slate-500 bg-slate-900/80 border border-slate-800 cursor-not-allowed">
                                    Waktu Telah Lewat
                                </button>
                            @elseif($isFull)
                                <!-- Button trigger waitlist modal with Alpine.js async submit -->
                                <div x-data="{
                                    open: false,
                                    name: '{{ Auth::user()->name ?? '' }}',
                                    phone: '{{ Auth::user()->phone ?? '' }}',
                                    count: 1,
                                    loading: false,
                                    successMsg: null,
                                    errorMsg: null,
                                    submit() {
                                        @if(!Auth::check())
                                            window.location.href = '{{ route('login', ['redirect_to' => route('schedule', ['date' => $session->date->toDateString()])]) }}';
                                            return;
                                        @endif
                                        this.loading = true;
                                        this.errorMsg = null;
                                        fetch('{{ route('schedule.waitlist', $session) }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                player_name: this.name,
                                                player_phone: this.phone,
                                                player_count: this.count
                                            })
                                        })
                                        .then(async res => {
                                            const data = await res.json();
                                            if (!res.ok) throw new Error(data.message || 'Gagal mendaftar antrean.');
                                            return data;
                                        })
                                        .then(data => {
                                            this.loading = false;
                                            this.successMsg = data.message;
                                        })
                                        .catch(err => {
                                            this.loading = false;
                                            this.errorMsg = err.message;
                                        });
                                    }
                                }">
                                    <button type="button" @click="open = true" class="px-5 py-3 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-amber-300 bg-amber-950/60 border border-amber-500/40 hover:bg-amber-900/60 transition-all text-center">
                                        Masuk Daftar Tunggu
                                    </button>

                                    <!-- Modal Overlay -->
                                    <div x-show="open" x-cloak class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                                        <div @click.away="open = false" class="bg-[#0f172a] border border-slate-700 p-6 rounded-2xl max-w-md w-full text-left space-y-4 shadow-2xl relative">
                                            <div class="flex items-center justify-between">
                                                <h4 class="font-orbitron font-bold text-white text-base">Daftar Tunggu Antrean</h4>
                                                <button type="button" @click="open = false" class="text-slate-400 hover:text-white p-1">✕</button>
                                            </div>

                                            <p class="text-xs text-slate-400">
                                                Jika ada slot terbuka pada sesi <strong>{{ $session->formatted_time_range }} WIB</strong>, kami akan memberikan penawaran sesuai urutan antrean.
                                            </p>

                                            <template x-if="successMsg">
                                                <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 text-xs flex items-start gap-2 shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                                                    <span>✓</span>
                                                    <span x-text="successMsg"></span>
                                                </div>
                                            </template>

                                            <template x-if="errorMsg">
                                                <div class="p-4 rounded-xl bg-red-950/80 border border-red-500/50 text-red-200 text-xs flex items-start gap-2">
                                                    <span>⚠️</span>
                                                    <span x-text="errorMsg"></span>
                                                </div>
                                            </template>

                                            <form x-show="!successMsg" @submit.prevent="submit()" class="space-y-3">
                                                <div>
                                                    <label class="block text-xs text-slate-300 mb-1">Nama Pemain <span class="text-red-400">*</span></label>
                                                    <input type="text" x-model="name" required class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs focus:border-amber-400 focus:outline-none">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-300 mb-1">Nomor WhatsApp Aktif <span class="text-red-400">*</span></label>
                                                    <input type="text" x-model="phone" required placeholder="081234567890" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs focus:border-amber-400 focus:outline-none">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-300 mb-1">Jumlah Pemain (1 - 4 Orang) <span class="text-red-400">*</span></label>
                                                    <input type="number" x-model="count" min="1" max="4" required class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs focus:border-amber-400 focus:outline-none">
                                                </div>
                                                <button type="submit" :disabled="loading" class="w-full py-2.5 rounded-lg font-orbitron font-bold text-xs uppercase bg-amber-400 hover:bg-amber-300 text-black flex items-center justify-center gap-2 disabled:opacity-50">
                                                    <span x-show="!loading">Konfirmasi Masuk Antrean</span>
                                                    <span x-show="loading" class="flex items-center gap-1.5">
                                                        <svg class="animate-spin h-3.5 w-3.5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                        Mendaftarkan...
                                                    </span>
                                                </button>
                                            </form>

                                            <div x-show="successMsg" class="pt-2">
                                                <button type="button" @click="open = false; successMsg = null;" class="w-full py-2 rounded-lg bg-slate-800 text-slate-300 text-xs font-semibold">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('booking.show', $session) }}" wire:navigate.hover class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-gradient-to-r from-cyan-400 to-blue-400 hover:from-cyan-300 hover:to-blue-300 transition-all shadow-[0_0_20px_rgba(6,182,212,0.4)]">
                                    Gabung Permainan →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 rounded-2xl bg-slate-900/50 border border-slate-800 text-center">
                    <span class="text-3xl block mb-2">🗓️</span>
                    <h4 class="font-orbitron font-bold text-white text-base">Tidak Ada Sesi Permainan</h4>
                    <p class="text-xs text-slate-400 mt-1">Belum ada jadwal sesi yang aktif pada tanggal ini. Silakan pilih tanggal lain.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

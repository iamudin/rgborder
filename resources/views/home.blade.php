@extends('layouts.app')

@section('content')
<div class="space-y-24 py-6">

    <!-- 1. HERO SECTION WITH MODERN PARALLAX SLIDER & TRANSPARENT HEXAGONAL STEEL OVERLAY -->
    <section class="relative overflow-hidden pt-10 pb-20 lg:py-28 min-h-[620px] lg:min-h-[720px] flex items-center justify-center border-b border-slate-800/80 -mt-6"
        x-data="{
            slides: {{ Js::from($heroSlides) }},
            enabled: {{ $heroSliderEnabled ? 'true' : 'false' }},
            activeSlide: 0,
            paused: false,
            progress: 0,
            mouseX: 0,
            mouseY: 0,
            timer: null,
            init() {
                if (this.enabled && this.slides.length > 1) {
                    this.startTimer();
                }
            },
            startTimer() {
                clearInterval(this.timer);
                this.timer = setInterval(() => {
                    if (!this.paused) {
                        this.progress += 2; // 50 steps * 100ms = 5 seconds
                        if (this.progress >= 100) {
                            this.next();
                            this.progress = 0;
                        }
                    }
                }, 100);
            },
            next() {
                this.activeSlide = (this.activeSlide + 1) % this.slides.length;
                this.progress = 0;
            },
            prev() {
                this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length;
                this.progress = 0;
            },
            setSlide(idx) {
                this.activeSlide = idx;
                this.progress = 0;
            },
            onMouseMove(e) {
                const rect = this.$el.getBoundingClientRect();
                this.mouseX = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
                this.mouseY = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
            },
            onMouseLeave() {
                this.mouseX = 0;
                this.mouseY = 0;
                this.paused = false;
            }
        }"
        @mousemove="onMouseMove($event)"
        @mouseleave="onMouseLeave()"
        @mouseenter="paused = true"
    >
        <!-- BACKGROUND LAYER 1: Modern Parallax Images (When Slider Enabled) -->
        <template x-if="enabled && slides.length > 0">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <template x-for="(slide, index) in slides" :key="slide.id || index">
                    <div x-show="activeSlide === index"
                         x-transition:enter="transition ease-out duration-1000 transform"
                         x-transition:enter-start="opacity-0 scale-105"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-800 transform absolute inset-0"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute inset-0 w-full h-full">
                        <img :src="slide.image" 
                             :alt="slide.title || 'RGB Arena Live Feed'" 
                             class="w-full h-full object-cover object-center will-change-transform"
                             :style="'transform: translate3d(' + (mouseX * -22) + 'px, ' + (mouseY * -14) + 'px, 0px) scale(1.10); transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);'" />
                    </div>
                </template>
            </div>
        </template>

        <!-- BACKGROUND LAYER 2: Transparent Hexagonal Steel Armor Mesh Overlay -->
        <div class="absolute inset-0 hex-steel-overlay opacity-75 mix-blend-screen pointer-events-none z-10 will-change-transform"
             :style="'transform: translate3d(' + (mouseX * 10) + 'px, ' + (mouseY * 6) + 'px, 0px); transition: transform 0.35s ease-out;'">
        </div>

        <!-- BACKGROUND LAYER 3: Tactical Cyber Vignette & Ambient RGB Light Reflections -->
        <div class="absolute inset-0 hex-steel-vignette pointer-events-none z-20"></div>
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[700px] h-[400px] bg-gradient-to-r from-red-600/25 via-purple-600/20 to-cyan-500/25 blur-[130px] pointer-events-none z-20"></div>

        <!-- LAYER 4: Foreground Tactical Hero Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-30 pt-6">
            
            <!-- Live Sector Status HUD Pill -->
            <template x-if="enabled && slides[activeSlide]">
                <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-slate-900/95 border border-cyan-500/50 text-cyan-300 text-xs font-mono font-semibold mb-6 shadow-[0_0_25px_rgba(6,182,212,0.4)] backdrop-blur-md">
                    <span class="w-2.5 h-2.5 rounded-full animate-ping"
                          :class="(slides[activeSlide].badge_color === 'red') ? 'bg-red-400' : 'bg-cyan-400'"></span>
                    <span class="tracking-widest uppercase" x-text="slides[activeSlide].tagline || 'LIVE COMBAT ARENA FEED'"></span>
                    <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase"
                          :class="(slides[activeSlide].badge_color === 'red') ? 'bg-red-950 text-red-300 border border-red-500/40' : 'bg-cyan-950 text-cyan-300 border border-cyan-500/40'"
                          x-text="slides[activeSlide].badge || 'ONLINE'"></span>
                </div>
            </template>

            <!-- Headline -->
            <h1 class="font-orbitron font-black text-4xl sm:text-6xl lg:text-7xl tracking-tight text-white uppercase leading-none drop-shadow-[0_10px_20px_rgba(0,0,0,0.8)]">
                Siap Bertempur di <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 via-purple-400 to-cyan-400 glow-text-cyan">
                    RGB ARENA?
                </span>
            </h1>

            <p class="max-w-2xl mx-auto text-slate-200 text-sm sm:text-base lg:text-lg mt-6 leading-relaxed drop-shadow-md">
                Rasakan sensasi baku tembak laser taktis 4 vs 4 berkecepatan tinggi dalam labirin bernuansa neon RGB futuristik. Pilih tim Anda, kunci strategi, dan rebut kemenangan!
            </p>

            <!-- Pricing & Info Badges Grid -->
            <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-6 my-8 text-xs font-mono">
                <div class="px-4 py-2.5 rounded-xl bg-slate-900/90 backdrop-blur-md border border-slate-700/80 flex items-center gap-2 shadow-xl hover:border-cyan-500/50 transition-colors">
                    <span class="text-base text-cyan-400">💰</span>
                    <span class="text-slate-300">Tiket: <strong class="text-cyan-400 font-bold">Rp{{ number_format($price, 0, ',', '.') }}</strong> / Pemain</span>
                </div>
                <div class="px-4 py-2.5 rounded-xl bg-slate-900/90 backdrop-blur-md border border-slate-700/80 flex items-center gap-2 shadow-xl hover:border-purple-500/50 transition-colors">
                    <span class="text-base text-purple-400">⏱️</span>
                    <span class="text-slate-300">Durasi: <strong class="text-purple-400 font-bold">{{ $duration }} Menit</strong> / Sesi</span>
                </div>
                <div class="px-4 py-2.5 rounded-xl bg-slate-900/90 backdrop-blur-md border border-slate-700/80 flex items-center gap-2 shadow-xl hover:border-red-500/50 transition-colors">
                    <span class="text-base text-red-400">⚔️</span>
                    <span class="text-slate-300">Format: <strong class="text-red-400 font-bold">4 vs 4</strong> (Tim Merah vs Biru)</span>
                </div>
            </div>

            <!-- Primary Actions -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('schedule') }}" wire:navigate.hover class="w-full sm:w-auto px-8 py-4 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-gradient-to-r from-cyan-400 via-cyan-300 to-emerald-400 hover:from-cyan-300 hover:to-emerald-300 transition-all shadow-[0_0_30px_rgba(6,182,212,0.5)] transform hover:-translate-y-0.5">
                    ⚡ Booking Tiket Sekarang
                </a>
                <a href="{{ route('schedule') }}" wire:navigate.hover class="w-full sm:w-auto px-8 py-4 rounded-xl font-orbitron font-semibold text-xs uppercase tracking-wider text-slate-200 bg-slate-900/90 backdrop-blur-md border border-slate-700 hover:border-cyan-400 hover:bg-slate-800 transition-all">
                    Lihat Jadwal Hari Ini
                </a>
            </div>

            <!-- LAYER 5: Parallax Slider Navigators & HUD Dots (When Slider Enabled and > 1 Slide) -->
            <template x-if="enabled && slides.length > 1">
                <div class="mt-12 flex flex-col items-center justify-center gap-4">
                    <!-- Slide Indicators with Timer Progress -->
                    <div class="flex items-center gap-2.5 bg-slate-950/80 px-4 py-2 rounded-full border border-slate-800/80 backdrop-blur-md shadow-2xl">
                        <template x-for="(slide, index) in slides" :key="slide.id || index">
                            <button type="button" 
                                    @click="setSlide(index)" 
                                    :title="slide.title || 'Slide ' + (index + 1)"
                                    class="group relative flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono transition-all"
                                    :class="activeSlide === index ? 'bg-cyan-950 text-cyan-300 border border-cyan-500/60 shadow-[0_0_12px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white'">
                                <span class="font-bold text-[11px]" x-text="'0' + (index + 1)"></span>
                                <span class="hidden sm:inline text-[10px] uppercase font-semibold" x-text="slide.title"></span>
                                
                                <!-- Progress line for active slide -->
                                <template x-if="activeSlide === index">
                                    <span class="w-8 h-1 bg-slate-800 rounded-full overflow-hidden inline-block ml-1">
                                        <span class="h-full bg-cyan-400 block transition-all" :style="'width: ' + progress + '%'"></span>
                                    </span>
                                </template>
                            </button>
                        </template>
                    </div>

                    <!-- Slide Pause / Auto status note -->
                    <span class="text-[10px] font-mono text-slate-400" x-text="paused ? '⏸️ Auto-slide dijeda (Hover mouse)' : '▶️ Menampilkan live arena feed'"></span>
                </div>
            </template>
        </div>

        <!-- Floating Left / Right Tactical Navigation Arrows -->
        <template x-if="enabled && slides.length > 1">
            <div class="absolute inset-y-0 inset-x-4 lg:inset-x-8 pointer-events-none flex items-center justify-between z-30">
                <button type="button" 
                        @click="prev()" 
                        class="pointer-events-auto p-3 rounded-2xl bg-slate-900/80 hover:bg-cyan-950/80 border border-slate-800 hover:border-cyan-500/60 text-slate-300 hover:text-cyan-300 transition-all shadow-[0_0_20px_rgba(0,0,0,0.5)] group backdrop-blur-md"
                        title="Slide Sebelumnya">
                    <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <button type="button" 
                        @click="next()" 
                        class="pointer-events-auto p-3 rounded-2xl bg-slate-900/80 hover:bg-cyan-950/80 border border-slate-800 hover:border-cyan-500/60 text-slate-300 hover:text-cyan-300 transition-all shadow-[0_0_20px_rgba(0,0,0,0.5)] group backdrop-blur-md"
                        title="Slide Selanjutnya">
                    <svg class="w-5 h-5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </template>
    </section>

    <!-- 2. JADWAL TERDEKAT / LIVE SLOT PREVIEW -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 gap-4">
            <div>
                <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Ketersediaan Slot Real-Time</span>
                <h2 class="font-orbitron font-bold text-2xl sm:text-3xl text-white tracking-wide mt-1">Sesi Pertempuran Terdekat</h2>
            </div>
            <a href="{{ route('schedule') }}" wire:navigate.hover class="text-xs font-semibold text-cyan-400 hover:text-cyan-300 inline-flex items-center gap-1 group">
                <span>Lihat Seluruh Jadwal</span>
                <span class="group-hover:translate-x-1 transition-transform">→</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($upcomingSessions as $session)
                @php
                    $isFull = $session->isFull();
                    $available = $session->available_slots_count;
                    $red = $session->redTeam;
                    $blue = $session->blueTeam;
                    $percent = round((($session->max_players - $available) / $session->max_players) * 100);
                @endphp
                <div class="p-5 rounded-2xl bg-[#0f172a]/80 border {{ $isFull ? 'border-slate-800 opacity-90' : 'border-slate-700/80 hover:border-cyan-500/60' }} transition-all flex flex-col justify-between group shadow-lg">
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-mono text-xs text-slate-400">{{ $session->date->format('d M Y') }}</span>
                            @if($isFull)
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-red-950/80 border border-red-500/40 text-red-300">PENUH</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950/80 border border-emerald-500/40 text-emerald-300">TERBUKA</span>
                            @endif
                        </div>

                        <!-- Time & Duration -->
                        <div class="mb-4">
                            <h3 class="font-orbitron font-extrabold text-2xl text-white tracking-wide group-hover:text-cyan-400 transition-colors">
                                {{ $session->formatted_time_range }} <span class="text-xs font-normal text-slate-400">WIB</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Durasi {{ $session->duration_minutes }} Menit &bull; Rp{{ number_format($session->price_per_player, 0, ',', '.') }}/org</p>
                        </div>

                        <!-- Team Roster Preview -->
                        <div class="grid grid-cols-2 gap-2 text-xs mb-4">
                            <div class="p-2.5 rounded-lg bg-red-950/30 border border-red-900/50">
                                <div class="flex items-center justify-between text-red-400 font-bold mb-1">
                                    <span>Tim Merah</span>
                                    <span>{{ $red?->occupied_slots_count ?? 0 }}/{{ $red?->max_players ?? 4 }}</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ (($red?->occupied_slots_count ?? 0) / ($red?->max_players ?? 4)) * 100 }}%"></div>
                                </div>
                            </div>

                            <div class="p-2.5 rounded-lg bg-blue-950/30 border border-blue-900/50">
                                <div class="flex items-center justify-between text-blue-400 font-bold mb-1">
                                    <span>Tim Biru</span>
                                    <span>{{ $blue?->occupied_slots_count ?? 0 }}/{{ $blue?->max_players ?? 4 }}</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ (($blue?->occupied_slots_count ?? 0) / ($blue?->max_players ?? 4)) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar Total -->
                        <div class="space-y-1 mb-4">
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>Keterisian Arena:</span>
                                <span class="font-bold {{ $available > 0 ? 'text-cyan-400' : 'text-red-400' }}">{{ $available }} Slot Tersisa</span>
                            </div>
                            <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-slate-800">
                                <div class="bg-gradient-to-r from-cyan-500 to-purple-500 h-2 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action -->
                    <div>
                        @if($isFull)
                            <a href="{{ route('schedule', ['date' => $session->date->toDateString()]) }}" wire:navigate.hover class="w-full py-2.5 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-amber-300 bg-amber-950/50 border border-amber-500/40 hover:bg-amber-900/60 transition-all text-center block">
                                Masuk Daftar Tunggu
                            </a>
                        @else
                            <a href="{{ route('booking.show', $session) }}" wire:navigate.hover class="w-full py-2.5 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-gradient-to-r from-cyan-400 to-blue-400 hover:from-cyan-300 hover:to-blue-300 transition-all text-center block shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                                Gabung Permainan
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-3 p-8 rounded-2xl bg-slate-900/50 border border-slate-800 text-center">
                    <p class="text-sm text-slate-400">Belum ada sesi yang dibuka. Silakan cek halaman jadwal untuk tanggal lainnya.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- 3. VIDEO TRAILER KESERUAN & GOOGLE MAPS LOKASI ARENA -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900 border border-cyan-500/40 text-cyan-400 font-mono text-xs font-semibold uppercase tracking-wider mb-2 shadow-[0_0_15px_rgba(6,182,212,0.25)]">
                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                <span>Trailer Aksi & Peta Markas Tempur</span>
            </span>
            <h2 class="font-orbitron font-extrabold text-2xl sm:text-4xl text-white tracking-wide mt-1">
                Keseruan Arena & Lokasi Tempur
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 max-w-2xl mx-auto mt-2">
                Saksikan cuplikan keseruan baku tembak laser combat dengan teknologi sensor presisi tinggi, dan temukan rute tercepat menuju arena pertempuran kami.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 lg:gap-8 items-stretch">
            
            <!-- COLUMN 1: YOUTUBE VIDEO TRAILER EMBED (70%) -->
            <div class="lg:col-span-7 p-6 sm:p-7 rounded-3xl bg-gradient-to-b from-[#0f172a]/95 via-[#0c1222]/90 to-[#090d16]/95 border border-slate-800 hover:border-cyan-500/50 transition-all duration-300 shadow-[0_10px_35px_rgba(0,0,0,0.5)] flex flex-col justify-between relative group">
                <!-- Decorative HUD Corner Notches -->
                <div class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-cyan-500/70 rounded-tl-2xl pointer-events-none"></div>
                <div class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-cyan-500/70 rounded-br-2xl pointer-events-none"></div>

                <div class="flex flex-col flex-1">
                    <!-- Header Info -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                            <span class="text-xs font-mono font-bold tracking-wider text-slate-200 uppercase">OFFICIAL BATTLE TRAILER</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-900 border border-slate-700 text-cyan-400">
                            4K ULTRA-HD
                        </span>
                    </div>

                    <h3 class="font-orbitron font-bold text-lg sm:text-xl text-white mb-1.5 flex items-center gap-2">
                        <span>Trailer Keseruan Arena RGB</span>
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed mb-5">
                        Lihat bagaimana keseruan baku tembak antar-tim, arena bertema sci-fi dengan sistem lampu RGB dinamis, dan rompi sensor nirkabel berakurasi tinggi.
                    </p>

                    <!-- Responsive YouTube Embed -->
                    <div class="aspect-video w-full flex-1 rounded-2xl overflow-hidden border border-slate-700/80 bg-black shadow-2xl relative group-hover:border-cyan-500/60 transition-colors">
                        <iframe 
                            src="{{ $youtubeEmbedUrl }}" 
                            title="Trailer Keseruan RGB Arena"
                            class="w-full h-full object-cover"
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: GOOGLE MAPS LOCATION EMBED (30%) -->
            <div class="lg:col-span-3 p-5 sm:p-6 rounded-3xl bg-gradient-to-b from-[#0f172a]/95 via-[#0c1222]/90 to-[#090d16]/95 border border-slate-800 hover:border-purple-500/50 transition-all duration-300 shadow-[0_10px_35px_rgba(0,0,0,0.5)] flex flex-col justify-between relative group">
                <!-- Decorative HUD Corner Notches -->
                <div class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-purple-500/70 rounded-tr-2xl pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-purple-500/70 rounded-bl-2xl pointer-events-none"></div>

                <div class="flex flex-col flex-1">
                    <!-- Header Info -->
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="text-xs font-mono font-bold tracking-wider text-slate-200 uppercase">RADAR LOKASI ARENA</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-900 border border-slate-700 text-emerald-400">
                            {{ $openTime }} - {{ $closeTime }} WIB
                        </span>
                    </div>

                    <h3 class="font-orbitron font-bold text-lg text-white mb-1.5 flex items-center gap-2">
                        <span>Peta & Rute Tempur Arena</span>
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed mb-4">
                        Lokasi arena sangat mudah diakses, dekat transportasi umum & area parkir luas.
                    </p>

                    <!-- Responsive Google Maps Embed -->
                    <div class="w-full flex-1 min-h-[220px] rounded-2xl overflow-hidden border border-slate-700/80 bg-slate-950 shadow-2xl relative group-hover:border-purple-500/60 transition-colors">
                        <iframe 
                            src="{{ $googleMapsEmbedUrl }}" 
                            title="Peta Lokasi Google Maps RGB Arena"
                            class="w-full h-full object-cover border-0 min-h-[220px]" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>

                <!-- Location Address & Actions -->
                <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-3">
                    <div class="flex items-start gap-2 text-xs text-slate-300">
                        <span class="text-cyan-400 text-base mt-0.5">📍</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-white text-[11px]">Alamat Lengkap:</div>
                            <div class="text-slate-400 text-[11px] leading-relaxed line-clamp-2">{{ $arenaAddress }}</div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-1">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($arenaAddress) }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="w-full py-2.5 px-3 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 transition-all text-center flex items-center justify-center gap-1.5 shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                            <span>Buka Rute di Google Maps</span>
                            <span class="text-sm">↗</span>
                        </a>

                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', $arenaWhatsapp) }}?text=Halo%20RGB%20Arena,%20saya%20ingin%20tanya%20arah%20lokasi%20arena" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="w-full py-2 px-3 rounded-xl font-orbitron font-semibold text-xs text-slate-300 bg-slate-900 border border-slate-700 hover:text-white hover:border-slate-600 transition-all text-center flex items-center justify-center gap-1.5">
                            <span class="text-emerald-400">💬</span>
                            <span>Tanya CS WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 4. CARA BERMAIN / HOW IT WORKS -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Langkah Mudah</span>
            <h2 class="font-orbitron font-bold text-2xl sm:text-3xl text-white tracking-wide mt-1">Cara Bermain di RGB Arena</h2>
            <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto mt-2">Daftar sendiri maupun berkelompok, sistem kami menjamin pembagian tim yang seimbang dan slot yang terkunci aman.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Step 1 -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 relative group hover:border-cyan-500/50 transition-all">
                <span class="font-orbitron font-black text-3xl text-cyan-500/20 group-hover:text-cyan-500/40 transition-colors">01</span>
                <h3 class="font-orbitron font-bold text-sm text-white mt-2 mb-2">Pilih Jadwal Sesi</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Pilih tanggal dan jam bermain favorit Anda. Anda bisa melihat ketersediaan slot kosong secara langsung.</p>
            </div>

            <!-- Step 2 -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 relative group hover:border-cyan-500/50 transition-all">
                <span class="font-orbitron font-black text-3xl text-purple-500/20 group-hover:text-purple-500/40 transition-colors">02</span>
                <h3 class="font-orbitron font-bold text-sm text-white mt-2 mb-2">Tentukan Tim & Format</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Daftar solo (1 orang), bareng kawan (2-4 orang), atau 1 tim penuh. Anggota kelompok dipastikan tetap satu tim.</p>
            </div>

            <!-- Step 3 -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 relative group hover:border-cyan-500/50 transition-all">
                <span class="font-orbitron font-black text-3xl text-emerald-500/20 group-hover:text-emerald-500/40 transition-colors">03</span>
                <h3 class="font-orbitron font-bold text-sm text-white mt-2 mb-2">Kunci Slot & Bayar</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Slot Anda di-hold sementara selama 10 menit. Lakukan pembayaran cepat via QRIS, Transfer Bank, atau Tunai.</p>
            </div>

            <!-- Step 4 -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 relative group hover:border-cyan-500/50 transition-all">
                <span class="font-orbitron font-black text-3xl text-red-500/20 group-hover:text-red-500/40 transition-colors">04</span>
                <h3 class="font-orbitron font-bold text-sm text-white mt-2 mb-2">Scan QR & Bertempur!</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Tunjukkan tiket QR di resepsionis untuk check-in otomatis, ambil rompi sensor dan senjata laser, lalu mulai bertarung!</p>
            </div>
        </div>
    </section>

    <!-- 4. PERATURAN ARENA -->
    <section id="rules" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-b from-[#0f172a] to-[#090d16] border border-slate-800 relative overflow-hidden">
            <div class="flex flex-col md:flex-row gap-8 items-start justify-between">
                <div class="max-w-md">
                    <span class="text-xs font-mono font-semibold tracking-wider text-red-400 uppercase">Keselamatan & Ketertiban</span>
                    <h2 class="font-orbitron font-bold text-2xl sm:text-3xl text-white tracking-wide mt-1">Peraturan Arena Pertempuran</h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-3 leading-relaxed">
                        Demi keselamatan dan pengalaman bermain maksimal bagi seluruh peserta, mohon patuhi peraturan ketat berikut selama berada di dalam RGB Arena.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1 w-full">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <div class="flex items-center gap-2.5 text-xs font-bold text-cyan-400 mb-1">
                            <span>🕒</span>
                            <span>Hadir Tepat Waktu</span>
                        </div>
                        <p class="text-[11px] text-slate-400">Wajib tiba di arena minimal 15 menit sebelum sesi untuk briefing taktis dan briefing peralatan sensor.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <div class="flex items-center gap-2.5 text-xs font-bold text-purple-400 mb-1">
                            <span>👟</span>
                            <span>Sepatu & Pakaian</span>
                        </div>
                        <p class="text-[11px] text-slate-400">Wajib menggunakan sepatu olahraga beralas karet datar. Dilarang memakai sandal atau sepatu hak tinggi.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <div class="flex items-center gap-2.5 text-xs font-bold text-red-400 mb-1">
                            <span>🚫</span>
                            <span>No Physical Contact</span>
                        </div>
                        <p class="text-[11px] text-slate-400">Dilarang melakukan kontak fisik, mendorong, memanjat rintangan, atau memegang senjata lawan.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <div class="flex items-center gap-2.5 text-xs font-bold text-emerald-400 mb-1">
                            <span>🛡️</span>
                            <span>Instruksi Marshal</span>
                        </div>
                        <p class="text-[11px] text-slate-400">Patuhi aba-aba wasit/marshal lapangan setiap saat. Pelanggaran berulang dapat dikeluarkan dari sesi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. FAQ SECTION -->
    <section id="faq" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Pertanyaan Umum</span>
            <h2 class="font-orbitron font-bold text-2xl sm:text-3xl text-white tracking-wide mt-1">FAQ Seputar RGB Arena</h2>
        </div>

        <div class="space-y-4">
            <details class="group p-5 rounded-xl bg-[#0f172a]/90 border border-slate-800 open:border-cyan-500/40 transition-colors">
                <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between list-none">
                    <span>Apakah bisa mendaftar jika saya datang sendirian tanpa teman?</span>
                    <span class="text-cyan-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                    Sangat bisa! Sistem kami secara otomatis memasukkan Anda ke dalam tim yang kekurangan pemain sehingga Anda dapat bermain bersama pemain lain yang hadir di sesi tersebut.
                </p>
            </details>

            <details class="group p-5 rounded-xl bg-[#0f172a]/90 border border-slate-800 open:border-cyan-500/40 transition-colors">
                <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between list-none">
                    <span>Jika saya memesan untuk 3 orang, apakah kami dipastikan satu tim?</span>
                    <span class="text-cyan-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                    Ya, pasti. Sistem pembagian tim RGB Arena memiliki aturan ketat di mana pendaftaran kelompok tidak pernah dipisahkan. Sesi hanya akan menerima booking kelompok jika kapasitas salah satu tim mencukupi seluruh anggota Anda.
                </p>
            </details>

            <details class="group p-5 rounded-xl bg-[#0f172a]/90 border border-slate-800 open:border-cyan-500/40 transition-colors">
                <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between list-none">
                    <span>Bagaimana cara kerja daftar tunggu jika sesi sudah penuh?</span>
                    <span class="text-cyan-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                    Jika ada pemain lain yang membatalkan booking atau batas waktu pembayarannya habis (expired), sistem otomatis menawarkan slot tersebut ke pendaftar antrean nomor teratas dengan durasi konfirmasi 15 menit.
                </p>
            </details>

            <details class="group p-5 rounded-xl bg-[#0f172a]/90 border border-slate-800 open:border-cyan-500/40 transition-colors">
                <summary class="font-bold text-sm text-slate-200 cursor-pointer flex items-center justify-between list-none">
                    <span>Bagaimana cara menggunakan tiket digital saat tiba di arena?</span>
                    <span class="text-cyan-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                    Buka menu "Booking Saya" pada smartphone Anda, pilih detail booking, dan tunjukkan QR Code tiket kepada petugas resepsionis arena untuk di-scan.
                </p>
            </details>
        </div>
    </section>

</div>
@endsection

<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'RGB Arena — Sistem Booking & Arena Tempur RGB' }}</title>
    <meta name="description" content="Sistem booking tiket, antrean, pembagian tim, dan operasional arena laser combat RGB terdepan.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800;900&family=Rajdhani:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .font-orbitron { font-family: 'Orbitron', sans-serif; }
        .font-rajdhani { font-family: 'Rajdhani', sans-serif; }
    </style>
</head>
<body class="bg-[#090d16] text-slate-100 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-black">
    <!-- Background cyber grid overlay -->
    <div class="fixed inset-0 pointer-events-none cyber-grid opacity-20 z-0"></div>

    <!-- Glowing top decorative bar -->
    <div class="h-1 w-full bg-gradient-to-r from-cyan-500 via-purple-500 to-red-500 shadow-[0_0_15px_rgba(6,182,212,0.5)] z-50"></div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-40 bg-[#090d16]/90 backdrop-blur-md border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" wire:navigate.hover class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 via-purple-600 to-cyan-400 p-[2px] group-hover:shadow-[0_0_18px_rgba(6,182,212,0.6)] transition-all duration-300">
                            <div class="w-full h-full bg-[#090d16] rounded-[6px] flex items-center justify-center">
                                <span class="font-orbitron font-black text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-cyan-300 text-lg">RGB</span>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-orbitron font-bold tracking-wider text-base text-white group-hover:text-cyan-400 transition-colors">RGB ARENA</span>
                            <span class="text-[10px] tracking-widest text-slate-400 font-rajdhani uppercase font-semibold">Laser Combat Arena</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                    <a href="{{ route('home') }}" wire:navigate.hover class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('home') ? 'text-cyan-400 bg-cyan-950/40 border border-cyan-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                        Beranda
                    </a>
                    <a href="{{ route('schedule') }}" wire:navigate.hover class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('schedule') ? 'text-cyan-400 bg-cyan-950/40 border border-cyan-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                        Jadwal Arena
                    </a>
                    <a href="{{ route('booking.index') }}" wire:navigate.hover class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('booking.*') ? 'text-cyan-400 bg-cyan-950/40 border border-cyan-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                        Booking Sesi
                    </a>
                    <a href="{{ route('pages.show', 'about') }}" wire:navigate.hover class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ (request()->is('info/about*') || request()->is('about') || request()->is('informasi/about*')) ? 'text-cyan-400 bg-cyan-950/40 border border-cyan-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                        Tentang Arena
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate.hover class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard') ? 'text-cyan-400 bg-cyan-950/40 border border-cyan-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('my-bookings') }}" wire:navigate.hover class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('my-bookings*') ? 'text-cyan-400 bg-cyan-950/40 border border-cyan-800/60' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Tiket & Booking Saya
                        </a>
                    @endauth
                </nav>

                <!-- User Section / CTA -->
                <div class="flex items-center gap-3">
                    @auth
                        <div class="relative flex items-center gap-3">
                            <a href="{{ route('profile') }}" wire:navigate.hover class="flex items-center gap-2 text-sm text-slate-300 hover:text-cyan-300 group">
                                @if(Auth::user()->avatar)
                                    <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full border border-cyan-500/50">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-cyan-950 border border-cyan-500/50 flex items-center justify-center text-cyan-400 font-bold text-xs">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                    </div>
                                @endif
                                <span class="hidden sm:inline font-medium text-xs text-slate-200 group-hover:text-cyan-300">{{ Auth::user()->name }}</span>
                            </a>

                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" title="Keluar" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-950/30 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" wire:navigate.hover class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold uppercase tracking-wider text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 transition-all shadow-[0_0_15px_rgba(6,182,212,0.4)]">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.761H12.545z"/>
                            </svg>
                            <span>Masuk</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Notifications Banner -->
    <div class="relative z-30 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if (session('success'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 flex items-center justify-between shadow-[0_0_20px_rgba(16,185,129,0.2)]">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚔️</span>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200">✕</button>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 rounded-xl bg-red-950/80 border border-red-500/40 text-red-200 flex items-center justify-between shadow-[0_0_20px_rgba(239,68,68,0.2)]">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚠️</span>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-200">✕</button>
            </div>
        @endif

        @if (session('warning'))
            <div class="mb-4 p-4 rounded-xl bg-amber-950/80 border border-amber-500/40 text-amber-200 flex items-center justify-between shadow-[0_0_20px_rgba(245,158,11,0.2)]">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚡</span>
                    <span class="text-sm font-medium">{{ session('warning') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-amber-400 hover:text-amber-200">✕</button>
            </div>
        @endif
    </div>

    <!-- Main Content Area (padding bottom for mobile bottom navigation bar) -->
    <main class="flex-grow relative z-10 pb-24 md:pb-0">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Mobile Bottom App Bar (SPA Speed & Instant Touch Navigation for Smartphones) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#090d16]/95 backdrop-blur-xl border-t border-slate-800/90 px-2 py-2 shadow-[0_-5px_25px_rgba(0,0,0,0.6)]">
        <div class="grid grid-cols-5 items-center justify-around text-center">
            <!-- Beranda -->
            <a href="{{ route('home') }}" wire:navigate.hover class="flex flex-col items-center py-1 transition-all {{ request()->routeIs('home') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-base">🏠</span>
                <span class="text-[10px] tracking-tight font-rajdhani font-semibold">Beranda</span>
            </a>

            <!-- Jadwal -->
            <a href="{{ route('schedule') }}" wire:navigate.hover class="flex flex-col items-center py-1 transition-all {{ request()->routeIs('schedule') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-base">🗓️</span>
                <span class="text-[10px] tracking-tight font-rajdhani font-semibold">Jadwal</span>
            </a>

            <!-- Booking Center Highlight -->
            <a href="{{ route('booking.index') }}" wire:navigate.hover class="flex flex-col items-center py-1 transition-all -mt-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-cyan-400 via-blue-500 to-purple-600 p-[2px] shadow-[0_0_18px_rgba(6,182,212,0.7)] flex items-center justify-center">
                    <div class="w-full h-full bg-[#090d16] rounded-full flex items-center justify-center">
                        <span class="text-xl">🎯</span>
                    </div>
                </div>
                <span class="text-[10px] tracking-wider font-bold text-cyan-400 font-orbitron mt-0.5">BOOKING</span>
            </a>

            <!-- Tiket Saya -->
            <a href="{{ route('my-bookings') }}" wire:navigate.hover class="flex flex-col items-center py-1 transition-all {{ request()->routeIs('my-bookings*') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-base">🎫</span>
                <span class="text-[10px] tracking-tight font-rajdhani font-semibold">Tiket</span>
            </a>

            <!-- Profil / Masuk -->
            <a href="{{ Auth::check() ? route('profile') : route('login') }}" wire:navigate.hover class="flex flex-col items-center py-1 transition-all {{ request()->routeIs('profile') || request()->routeIs('login') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <span class="text-base">👤</span>
                <span class="text-[10px] tracking-tight font-rajdhani font-semibold">{{ Auth::check() ? 'Profil' : 'Masuk' }}</span>
            </a>
        </div>
    </nav>

    <!-- Footer -->
    <footer class="relative z-20 mt-16 bg-[#070a10] border-t border-slate-800/80 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded bg-gradient-to-br from-red-500 via-purple-600 to-cyan-400 flex items-center justify-center p-[2px]">
                            <div class="w-full h-full bg-[#090d16] rounded-[2px] flex items-center justify-center">
                                <span class="font-orbitron font-black text-cyan-400 text-xs">RGB</span>
                            </div>
                        </div>
                        <span class="font-orbitron font-bold text-white tracking-wider">RGB ARENA INDONESIA</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-md mb-4">
                        Arena pertempuran laser combat berkecepatan tinggi dengan efek cahaya RGB, sistem pembagian tim otomatis, dan pelacakan antrean real-time.
                    </p>
                    <div class="flex items-center gap-4 text-xs text-slate-300">
                        <span class="inline-flex items-center gap-1.5 text-cyan-400">
                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                            Buka Setiap Hari: 10:00 - 22:00 WIB
                        </span>
                    </div>
                </div>

                <div>
                    <h4 class="font-orbitron text-xs font-bold text-slate-200 uppercase tracking-wider mb-3">Navigasi Arena</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="{{ route('home') }}" wire:navigate.hover class="hover:text-cyan-400 transition-colors">Beranda</a></li>
                        <li><a href="{{ route('schedule') }}" wire:navigate.hover class="hover:text-cyan-400 transition-colors">Jadwal Sesi</a></li>
                        <li><a href="{{ route('booking.index') }}" wire:navigate.hover class="hover:text-cyan-400 transition-colors">Pesan Tiket</a></li>
                        <li><a href="{{ route('pages.show', 'about') }}" wire:navigate.hover class="hover:text-cyan-400 transition-colors">Tentang RGB Arena</a></li>
                        <li><a href="{{ route('pages.show', 'rules') }}" wire:navigate.hover class="hover:text-cyan-400 transition-colors">Aturan & Keselamatan</a></li>
                        <li><a href="{{ route('pages.show', 'facilities') }}" wire:navigate.hover class="hover:text-cyan-400 transition-colors">Fasilitas Tempur</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-orbitron text-xs font-bold text-slate-200 uppercase tracking-wider mb-3">Kontak Arena</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li class="flex items-center gap-2">
                            <span class="text-cyan-400">💬</span>
                            <span>WhatsApp: {{ \App\Models\Setting::get('arena_whatsapp', '081234567890') }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-cyan-400">📍</span>
                            <span>Jl. Arena Perjuangan No. 88, Jakarta Selatan</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-cyan-400">💰</span>
                            <span>Harga: Rp{{ number_format(\App\Models\Setting::get('price_per_player', 25000), 0, ',', '.') }} / 30 Menit</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-slate-800/60 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <p>&copy; {{ date('Y') }} RGB Arena. Hak Cipta Dilindungi.</p>
                <div class="flex items-center gap-4">
                    <span class="text-slate-400">Sistem Berjalan Stabil</span>
                    <a href="/admin" class="text-slate-400 hover:text-cyan-400 transition-colors text-[11px]">Portal Petugas</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

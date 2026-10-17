<x-filament-panels::page>
    <div class="space-y-6" wire:poll.10s>

        <!-- TOP METRICS STRIP -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-gradient-to-br from-[#0c1322] to-[#111c33] border border-cyan-500/30 shadow-[0_0_20px_rgba(6,182,212,0.1)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-mono text-cyan-400 font-bold uppercase tracking-wider">Hadir di Arena</span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                </div>
                <div class="font-orbitron font-extrabold text-2xl text-white mt-1">
                    {{ $totalCheckedInToday }} <span class="text-xs font-normal text-slate-400">Pemain</span>
                </div>
                <span class="text-[11px] font-mono text-emerald-400 mt-1 block">Check-in Terkonfirmasi</span>
            </div>

            <div class="p-4 rounded-2xl bg-gradient-to-br from-[#0c1322] to-[#111c33] border border-amber-500/30 shadow-[0_0_20px_rgba(245,158,11,0.1)]">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-mono text-amber-400 font-bold uppercase tracking-wider">Menunggu Hadir</span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                </div>
                <div class="font-orbitron font-extrabold text-2xl text-amber-300 mt-1">
                    {{ $pendingCountToday }} <span class="text-xs font-normal text-slate-400">Pemain</span>
                </div>
                <span class="text-[11px] font-mono text-slate-400 mt-1 block">Belum Masuk Resepsionis</span>
            </div>

            <div class="p-4 rounded-2xl bg-gradient-to-br from-[#0c1322] to-[#111c33] border border-slate-700/80">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-mono text-slate-300 font-bold uppercase tracking-wider">Total Booking Hari Ini</span>
                    <span class="text-xs">🎮</span>
                </div>
                <div class="font-orbitron font-extrabold text-2xl text-white mt-1">
                    {{ $totalConfirmedToday }} <span class="text-xs font-normal text-slate-400">Slot Pemain</span>
                </div>
                <span class="text-[11px] font-mono text-slate-400 mt-1 block">Dari {{ count($sessionsToday) }} Sesi Jadwal</span>
            </div>

            <div class="p-4 rounded-2xl bg-gradient-to-br from-[#0c1322] to-[#111c33] border border-slate-700/80 flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono text-slate-400">
                    <span>Waktu Server Arena:</span>
                    <span class="text-emerald-400">● Live</span>
                </div>
                <div class="font-mono font-extrabold text-xl text-cyan-300" 
                     x-data="{ time: '{{ $currentTime }}' }" 
                     x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour12: false }), 1000)" 
                     x-text="time + ' WIB'">
                    {{ $currentTime }} WIB
                </div>
                <span class="text-[10px] text-slate-400 font-mono">{{ $currentDate }}</span>
            </div>
        </div>

        <!-- MAIN 2-COLUMN OPERATIONAL WORKBENCH -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6"
             x-data="{
                scannerStarted: false,
                html5QrCode: null,
                activeCamera: null,
                cameras: [],
                continuousMode: true,
                isScanningLocked: false,
                barcodeBuffer: '',
                lastBarcodeKeyTime: 0,

                initScanner() {
                    const loadScript = () => {
                        if (window.Html5Qrcode) {
                            this.setupCameras();
                            return;
                        }
                        const s = document.createElement('script');
                        s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                        s.onload = () => this.setupCameras();
                        document.head.appendChild(s);
                    };
                    loadScript();

                    // Hardware USB 2D Barcode Scanner listener (acts like keyboard input)
                    window.addEventListener('keydown', (e) => {
                        // Ignore if user is manually typing in input fields
                        if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;

                        const now = Date.now();
                        if (now - this.lastBarcodeKeyTime > 150) {
                            this.barcodeBuffer = '';
                        }
                        this.lastBarcodeKeyTime = now;

                        if (e.key === 'Enter') {
                            if (this.barcodeBuffer.length > 3) {
                                this.playBeep();
                                $wire.processCheckIn(this.barcodeBuffer);
                                this.barcodeBuffer = '';
                            }
                        } else if (e.key.length === 1) {
                            this.barcodeBuffer += e.key;
                        }
                    });
                },

                setupCameras() {
                    if (!window.Html5Qrcode) return;
                    this.html5QrCode = new Html5Qrcode('main-qr-reader');
                    Html5Qrcode.getCameras().then(devices => {
                        if (devices && devices.length) {
                            this.cameras = devices;
                            const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('environment')) || devices[0];
                            this.activeCamera = backCam.id;
                            this.startScanning();
                        }
                    }).catch(err => {
                        console.warn('Camera error:', err);
                    });
                },

                startScanning() {
                    if (!this.html5QrCode || !this.activeCamera) return;
                    if (this.scannerStarted) {
                        this.stopScanning().then(() => this.startScanning());
                        return;
                    }

                    this.html5QrCode.start(
                        this.activeCamera,
                        { fps: 20, qrbox: { width: 280, height: 280 } },
                        (decodedText) => {
                            if (this.isScanningLocked) return;
                            this.isScanningLocked = true;
                            this.playBeep();
                            $wire.processCheckIn(decodedText);

                            // Brief lock to prevent double scan on same QR
                            setTimeout(() => {
                                this.isScanningLocked = false;
                            }, 2000);
                        },
                        (err) => {}
                    ).then(() => {
                        this.scannerStarted = true;
                    }).catch(err => {
                        console.warn('Start scanning err:', err);
                    });
                },

                stopScanning() {
                    if (this.html5QrCode && this.scannerStarted) {
                        return this.html5QrCode.stop().then(() => {
                            this.scannerStarted = false;
                        }).catch(() => {
                            this.scannerStarted = false;
                        });
                    }
                    return Promise.resolve();
                },

                switchCamera(deviceId) {
                    this.activeCamera = deviceId;
                    this.stopScanning().then(() => this.startScanning());
                },

                playBeep(success = true) {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = success ? 'sine' : 'sawtooth';
                        osc.frequency.value = success ? 880 : 220;
                        gain.gain.value = 0.2;
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + (success ? 0.12 : 0.25));
                    } catch(e) {}
                }
             }"
             x-init="initScanner()">

            <!-- LEFT WORKBENCH: SCANNER VIEWFINDER & HARDWARE CONTROLS (5 cols) -->
            <div class="lg:col-span-5 space-y-4">

                <!-- Scanner Card -->
                <div class="rounded-3xl bg-[#090d16] border border-cyan-500/40 p-5 shadow-[0_0_35px_rgba(6,182,212,0.15)] space-y-4 relative overflow-hidden">
                    
                    <!-- Scanner Header & Controls -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-cyan-400 animate-ping"></span>
                            <span class="font-orbitron font-bold text-sm text-white">Kamera Scanner Resepsionis</span>
                        </div>
                        
                        <!-- Camera Selector -->
                        <template x-if="cameras.length > 1">
                            <select @change="switchCamera($event.target.value)" 
                                    class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700 text-[11px] font-mono text-cyan-300 focus:outline-none">
                                <template x-for="cam in cameras" :key="cam.id">
                                    <option :value="cam.id" x-text="cam.label || 'Kamera ' + cam.id"></option>
                                </template>
                            </select>
                        </template>
                    </div>

                    <!-- Viewfinder Video Viewport -->
                    <div class="relative rounded-2xl overflow-hidden border-2 border-cyan-500/50 bg-black min-h-[300px] flex items-center justify-center">
                        <div id="main-qr-reader" class="w-full h-full min-h-[300px]"></div>

                        <!-- High Tech Cyber Overlay -->
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                            <div class="w-64 h-64 border-2 border-cyan-400/80 rounded-2xl relative shadow-[0_0_25px_rgba(6,182,212,0.3)]">
                                <div class="absolute top-0 left-0 w-5 h-5 border-t-4 border-l-4 border-cyan-300 -mt-1 -ml-1"></div>
                                <div class="absolute top-0 right-0 w-5 h-5 border-t-4 border-r-4 border-cyan-300 -mt-1 -mr-1"></div>
                                <div class="absolute bottom-0 left-0 w-5 h-5 border-b-4 border-l-4 border-cyan-300 -mb-1 -ml-1"></div>
                                <div class="absolute bottom-0 right-0 w-5 h-5 border-b-4 border-r-4 border-cyan-300 -mb-1 -mr-1"></div>

                                <!-- Scanning Laser Line -->
                                <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-red-500 to-transparent shadow-[0_0_12px_rgba(239,68,68,0.9)] animate-pulse mt-28"></div>
                            </div>
                        </div>

                        <!-- Hardware Scanner Ready Badge -->
                        <div class="absolute bottom-2 left-2 right-2 px-3 py-1 rounded-lg bg-black/80 backdrop-blur border border-slate-800 text-[10px] font-mono text-slate-400 flex items-center justify-between">
                            <span>⚡ Mode: Auto-Scan Aktif</span>
                            <span class="text-cyan-400">USB Barcode Scanner Ready</span>
                        </div>
                    </div>

                    <!-- RECENT CHECK-IN RESULT CARD -->
                    @if($lastResult)
                        @if($lastResult['status'] === 'success')
                            <div class="p-4 rounded-2xl bg-emerald-950/90 border border-emerald-500 shadow-[0_0_25px_rgba(16,185,129,0.35)] animate-pulse">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full bg-emerald-500 text-black flex items-center justify-center font-extrabold text-2xl flex-shrink-0">
                                        ✓
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="font-orbitron font-extrabold text-xs text-emerald-300 tracking-wider">CHECK-IN BERHASIL!</span>
                                            <span class="text-[10px] font-mono text-emerald-400">{{ $lastResult['time'] }}</span>
                                        </div>
                                        <h4 class="text-base text-white font-bold truncate mt-0.5">
                                            {{ $lastResult['player_name'] }}
                                        </h4>
                                        <div class="text-[11px] text-slate-300 font-mono mt-0.5 flex flex-wrap gap-2">
                                            <span>Callsign: <strong class="text-cyan-300">{{ $lastResult['callsign'] }}</strong></span>
                                            <span>&bull;</span>
                                            <span class="{{ $lastResult['team_color'] === 'red' ? 'text-red-400 font-bold' : 'text-blue-400 font-bold' }}">
                                                {{ $lastResult['team_name'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-2xl bg-red-950/90 border border-red-500 text-xs text-red-200 flex items-start gap-3">
                                <span class="text-2xl">⚠️</span>
                                <div>
                                    <div class="font-orbitron font-bold text-red-300">Gagal Check-In</div>
                                    <div class="mt-0.5">{{ $lastResult['message'] }}</div>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- MANUAL INPUT FALLBACK -->
                    <form wire:submit.prevent="processCheckIn()" class="space-y-2 pt-2 border-t border-slate-800">
                        <label class="text-[11px] font-mono text-slate-400 block">
                            Atau input Token QR / Kode Booking manual:
                        </label>
                        <div class="flex gap-2">
                            <input type="text" 
                                   wire:model="manualCode" 
                                   placeholder="Contoh: CHK-abcdef1234 atau BOOK-202610..." 
                                   class="flex-1 px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-cyan-400" />
                            <button type="submit" 
                                    class="px-5 py-2.5 rounded-xl font-mono font-bold text-xs bg-gradient-to-r from-emerald-400 to-cyan-400 text-black hover:from-emerald-300 hover:to-cyan-300 transition-all cursor-pointer">
                                Check-In
                            </button>
                        </div>
                    </form>

                </div>

            </div>

            <!-- RIGHT WORKBENCH: LIVE ROSTER & SESSION ATTENDANCE (7 cols) -->
            <div class="lg:col-span-7 space-y-4">

                <!-- Session Filter Dropdown / Tabs -->
                <div class="p-4 rounded-2xl bg-[#090d16] border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🎯</span>
                        <span class="font-orbitron font-bold text-xs uppercase tracking-wider text-slate-200">
                            Pilih Sesi Jadwal
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <select wire:model.live="selectedSessionId" 
                                class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-cyan-300 focus:outline-none focus:border-cyan-400">
                            <option value="">-- Semua Sesi Hari Ini --</option>
                            @foreach($sessionsToday as $s)
                                <option value="{{ $s->id }}">
                                    {{ $s->session_name }} ({{ $s->formatted_time_range }}) — {{ $s->status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- TABS CONTENT: HADIR (CHECKED IN) VS MENUNGGU HADIR (PENDING) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- COLUMN A: SUDAH CHECK IN (HADIR) -->
                    <div class="p-4 rounded-2xl bg-[#090d16] border border-emerald-500/30 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                    <h4 class="font-orbitron font-bold text-xs text-white uppercase">Pemain Hadir ({{ count($checkedInPlayers) }})</h4>
                                </div>
                                <span class="text-[10px] font-mono text-emerald-400">Siap Tempur</span>
                            </div>

                            <div class="space-y-2.5 max-h-[460px] overflow-y-auto pr-1">
                                @forelse($checkedInPlayers as $cp)
                                    @php
                                        $team = $cp->team;
                                        $isRed = $team && str_contains(strtolower($team->name), 'merah');
                                    @endphp
                                    <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-xs font-mono flex items-center justify-between">
                                        <div class="min-w-0 pr-2">
                                            <div class="font-bold text-white truncate">
                                                {{ $cp->player_name }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $isRed ? 'bg-red-950 text-red-400 border border-red-800/50' : 'bg-blue-950 text-blue-400 border border-blue-800/50' }}">
                                                    {{ $team?->name ?? 'Tim Menunggu' }}
                                                </span>
                                                <span>&bull;</span>
                                                <span>{{ $cp->booking->gameSession->session_name }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="text-emerald-400 text-[11px] font-bold block">✓ Hadir</span>
                                            <span class="text-[10px] text-slate-500">{{ $cp->checked_in_at?->format('H:i') }} WIB</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-xs font-mono text-slate-500">
                                        Belum ada pemain yang check-in pada filter ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- COLUMN B: BELUM CHECK IN (MENUNGGU KEHADIRAN) -->
                    <div class="p-4 rounded-2xl bg-[#090d16] border border-amber-500/30 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                    <h4 class="font-orbitron font-bold text-xs text-white uppercase">Menunggu Hadir ({{ count($pendingPlayers) }})</h4>
                                </div>
                                <span class="text-[10px] font-mono text-amber-400">Belum Datang</span>
                            </div>

                            <div class="space-y-2.5 max-h-[460px] overflow-y-auto pr-1">
                                @forelse($pendingPlayers as $pp)
                                    @php
                                        $team = $pp->team;
                                        $isRed = $team && str_contains(strtolower($team->name), 'merah');
                                    @endphp
                                    <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-xs font-mono flex items-center justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-slate-200 truncate">
                                                {{ $pp->player_name }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $isRed ? 'bg-red-950 text-red-400 border border-red-800/50' : 'bg-blue-950 text-blue-400 border border-blue-800/50' }}">
                                                    {{ $team?->name ?? 'Tim Menunggu' }}
                                                </span>
                                                <span>&bull;</span>
                                                <span>{{ $pp->booking->gameSession->session_name }}</span>
                                            </div>
                                        </div>
                                        <button type="button" 
                                                wire:click="checkInPlayerDirectly({{ $pp->id }})"
                                                wire:confirm="Tandai pemain {{ $pp->player_name }} hadir secara manual?"
                                                class="px-2.5 py-1.5 rounded-lg bg-emerald-950 text-emerald-300 border border-emerald-700 hover:bg-emerald-900 transition-all text-[10px] font-bold whitespace-nowrap cursor-pointer">
                                            Tandai Hadir
                                        </button>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-xs font-mono text-emerald-400">
                                        🎉 Seluruh pemain pada filter ini telah hadir!
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</x-filament-panels::page>

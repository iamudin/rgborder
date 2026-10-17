<x-filament-widgets::widget>
    <div class="space-y-4">

        <!-- Top Header & Live QR Action Bar -->
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-[#0b1329] via-[#0f172a] to-[#111827] border border-cyan-500/30 shadow-[0_0_25px_rgba(6,182,212,0.15)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-950/80 border border-cyan-500/50 flex items-center justify-center text-cyan-400 font-black text-lg shadow-[0_0_15px_rgba(6,182,212,0.4)]">
                    ⚡
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold uppercase tracking-wider text-cyan-400">Pusat Komando Operasional Arena</span>
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-300 font-mono mt-0.5">
                        <span class="font-bold text-white text-sm" x-data="{ time: '{{ $currentTime }}' }" x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour12: false }), 1000)" x-text="time + ' WIB'">
                            {{ $currentTime }} WIB
                        </span>
                        <span>&bull;</span>
                        <span class="text-slate-400">{{ $currentDateFormatted }}</span>
                    </div>
                </div>
            </div>

            <!-- Prominent Check-in QR Camera Button -->
            <div class="flex items-center gap-2.5">
                <button type="button" 
                        wire:click="openQrModal"
                        class="px-5 py-2.5 rounded-xl font-mono font-bold text-xs uppercase tracking-wider text-black bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 transition-all flex items-center gap-2 shadow-[0_0_20px_rgba(16,185,129,0.4)] hover:scale-105 active:scale-95 cursor-pointer">
                    <span class="text-base">📷</span>
                    <span>Scan QR Kamera / Check-In</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-950 animate-ping"></span>
                </button>
            </div>
        </div>

        <!-- 2-COLUMN HERO STATUS: ACTIVE COMBAT VS NEXT UPCOMING SESSION -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- CARD 1: SESI SEDANG BERJALAN DI JAM SAAT INI -->
            <div class="p-5 sm:p-6 rounded-2xl bg-[#0f172a]/95 border {{ $activeSession ? ($activeSession->isPlaying() ? 'border-amber-500/60 shadow-[0_0_30px_rgba(245,158,11,0.2)]' : 'border-blue-500/50') : 'border-slate-800' }} flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 {{ $activeSession ? 'border-amber-400' : 'border-slate-700' }}"></div>
                <div class="absolute bottom-0 right-0 w-4 h-4 border-b-2 border-r-2 {{ $activeSession ? 'border-amber-400' : 'border-slate-700' }}"></div>

                @if($activeSession)
                    <div>
                        <!-- Header Status -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $activeSession->isPlaying() ? 'bg-amber-400 animate-pulse' : 'bg-blue-400 animate-ping' }}"></span>
                                <span class="text-xs font-mono font-bold tracking-wider uppercase {{ $activeSession->isPlaying() ? 'text-amber-400' : 'text-blue-400' }}">
                                    {{ $activeSession->isPlaying() ? 'PERTEMPURAN AKTIF SAAT INI' : 'SESI JADWAL SAAT INI (SIAP TEMPUR)' }}
                                </span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase {{ $activeSession->isPlaying() ? 'bg-amber-950 border border-amber-500 text-amber-300' : 'bg-blue-950 border border-blue-500 text-blue-300' }}">
                                {{ $activeSession->status }}
                            </span>
                        </div>

                        <!-- Session Code & Time Range -->
                        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 mb-4">
                            <div>
                                <span class="text-xs font-mono text-slate-400">{{ $activeSession->session_code }}</span>
                                <h3 class="font-orbitron font-extrabold text-2xl text-white tracking-wide mt-0.5">
                                    {{ $activeSession->formatted_time_range }} <span class="text-xs font-normal text-slate-400">WIB</span>
                                </h3>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-[11px] text-slate-400 font-mono">Kehadiran Pemain:</span>
                                <div class="font-mono font-bold text-sm text-cyan-400">
                                    {{ $activeCheckedInCount }} / {{ $activeTotalPlayers }} Hadir ({{ $activeSession->max_players }} Maks)
                                </div>
                            </div>
                        </div>

                        <!-- Live Timer Progress if Playing -->
                        @if($activeSession->isPlaying())
                            <div class="space-y-1.5 mb-4 p-3 rounded-xl bg-amber-950/20 border border-amber-900/40">
                                <div class="flex items-center justify-between text-xs font-mono text-amber-300">
                                    <span>Sisa Waktu Pertempuran:</span>
                                    <span class="font-bold text-sm">
                                        {{ gmdate('i:s', $activeSession->getRemainingSeconds()) }}
                                    </span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-amber-900/40">
                                    <div class="bg-gradient-to-r from-amber-500 to-red-500 h-2 rounded-full transition-all duration-1000" style="width: {{ $activeSession->getTimerProgressPercentage() }}%"></div>
                                </div>
                            </div>
                        @endif

                        <!-- Rosters Breakdown -->
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            @php
                                $red = $activeSession->redTeam;
                                $blue = $activeSession->blueTeam;
                            @endphp
                            <!-- Tim Merah -->
                            <div class="p-3 rounded-xl bg-red-950/20 border border-red-900/50">
                                <div class="flex items-center justify-between text-xs font-bold text-red-400 mb-1">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span>Tim Merah</span>
                                    </span>
                                    <span>{{ $red?->occupied_slots_count ?? 0 }}/{{ $red?->max_players ?? 4 }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $red?->bookingPlayers->pluck('player_name')->implode(', ') ?: 'Belum ada pemain' }}
                                </div>
                            </div>

                            <!-- Tim Biru -->
                            <div class="p-3 rounded-xl bg-blue-950/20 border border-blue-900/50">
                                <div class="flex items-center justify-between text-xs font-bold text-blue-400 mb-1">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span>Tim Biru</span>
                                    </span>
                                    <span>{{ $blue?->occupied_slots_count ?? 0 }}/{{ $blue?->max_players ?? 4 }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $blue?->bookingPlayers->pluck('player_name')->implode(', ') ?: 'Belum ada pemain' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Buttons for Active Session -->
                    <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-3">
                        <a href="{{ \App\Filament\Resources\GameSessions\Pages\SessionDetail::getUrl(['record' => $activeSession]) }}"
                           class="px-4 py-2 rounded-xl text-xs font-mono font-bold text-cyan-300 bg-cyan-950/60 border border-cyan-800 hover:bg-cyan-900/60 transition-all flex items-center gap-1.5">
                            <span>🖥️ Buka Ruang Kontrol</span>
                            <span>&rarr;</span>
                        </a>

                        @if($activeSession->isPlaying())
                            <button type="button"
                                    wire:click="finishGameSession({{ $activeSession->id }})"
                                    wire:confirm="Yakin ingin menyelesaikan sesi ini sekarang?"
                                    class="px-4 py-2 rounded-xl text-xs font-mono font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-all">
                                🏁 Tandai Selesai
                            </button>
                        @elseif(in_array($activeSession->status, ['ready', 'open', 'full']) && $activeSession->confirmed_slots_count > 0)
                            <button type="button"
                                    wire:click="startGameSession({{ $activeSession->id }})"
                                    wire:confirm="Mulai pertempuran untuk sesi ini?"
                                    class="px-4 py-2 rounded-xl text-xs font-mono font-bold text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                                ▶️ Mulai Permainan
                            </button>
                        @endif
                    </div>
                @else
                    <!-- Standby State -->
                    <div class="py-8 text-center flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 text-xl mb-3">
                            ⏸️
                        </div>
                        <h4 class="font-orbitron font-bold text-slate-200 text-sm">Tidak Ada Sesi Berlangsung</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                            Saat ini arena berada dalam status jeda persiapan atau belum ada sesi yang dimulai.
                        </p>
                    </div>
                @endif
            </div>

            <!-- CARD 2: SESI PALING DEKAT BERIKUTNYA -->
            <div class="p-5 sm:p-6 rounded-2xl bg-[#0f172a]/95 border {{ $nextSession ? 'border-cyan-500/50 shadow-[0_0_25px_rgba(6,182,212,0.15)]' : 'border-slate-800' }} flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 {{ $nextSession ? 'border-cyan-400' : 'border-slate-700' }}"></div>
                <div class="absolute bottom-0 left-0 w-4 h-4 border-b-2 border-l-2 {{ $nextSession ? 'border-cyan-400' : 'border-slate-700' }}"></div>

                @if($nextSession)
                    <div>
                        <!-- Header Status -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
                                <span class="text-xs font-mono font-bold tracking-wider uppercase text-cyan-400">
                                    SESI PALING DEKAT BERIKUTNYA
                                </span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-slate-900 border border-cyan-500/60 text-cyan-300">
                                {{ $nextSession->status }}
                            </span>
                        </div>

                        <!-- Session Code & Time Range -->
                        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 mb-4">
                            <div>
                                <span class="text-xs font-mono text-slate-400">{{ $nextSession->session_code }} ({{ $nextSession->date->format('d M') }})</span>
                                <h3 class="font-orbitron font-extrabold text-2xl text-white tracking-wide mt-0.5">
                                    {{ $nextSession->formatted_time_range }} <span class="text-xs font-normal text-slate-400">WIB</span>
                                </h3>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-[11px] text-slate-400 font-mono">Estimasi Mulai:</span>
                                <div class="font-mono font-bold text-sm text-amber-400 flex items-center sm:justify-end gap-1">
                                    <span>⏳</span>
                                    <span>{{ $nextCountdownText }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Slots & Check-in Progress -->
                        <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-2 mb-4">
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span class="text-slate-300">Slot Terisi:</span>
                                <span class="font-bold {{ $nextSession->available_slots_count > 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                    {{ $nextSession->occupied_slots_count }} / {{ $nextSession->max_players }} Pemain
                                    <span class="text-slate-400">({{ $nextSession->available_slots_count }} Slot Sisa)</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span class="text-slate-300">Status Check-in Tiket:</span>
                                <span class="font-bold text-cyan-400">
                                    {{ $nextCheckedInCount }} dari {{ $nextTotalPlayers }} Hadir di Arena
                                </span>
                            </div>
                        </div>

                        <!-- Rosters Preview -->
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            @php
                                $nextRed = $nextSession->redTeam;
                                $nextBlue = $nextSession->blueTeam;
                            @endphp
                            <div class="p-2.5 rounded-xl bg-red-950/20 border border-red-900/40 text-xs">
                                <div class="flex items-center justify-between text-red-400 font-bold mb-1">
                                    <span>Tim Merah</span>
                                    <span>{{ $nextRed?->occupied_slots_count ?? 0 }}/{{ $nextRed?->max_players ?? 4 }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $nextRed?->bookingPlayers->pluck('player_name')->implode(', ') ?: 'Menunggu pemain' }}
                                </div>
                            </div>

                            <div class="p-2.5 rounded-xl bg-blue-950/20 border border-blue-900/40 text-xs">
                                <div class="flex items-center justify-between text-blue-400 font-bold mb-1">
                                    <span>Tim Biru</span>
                                    <span>{{ $nextBlue?->occupied_slots_count ?? 0 }}/{{ $nextBlue?->max_players ?? 4 }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $nextBlue?->bookingPlayers->pluck('player_name')->implode(', ') ?: 'Menunggu pemain' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Buttons for Next Session -->
                    <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-3">
                        <a href="{{ \App\Filament\Resources\GameSessions\Pages\SessionDetail::getUrl(['record' => $nextSession]) }}"
                           class="px-4 py-2 rounded-xl text-xs font-mono font-bold text-cyan-300 bg-cyan-950/60 border border-cyan-800 hover:bg-cyan-900/60 transition-all flex items-center gap-1.5">
                            <span>🖥️ Ruang Kontrol Sesi</span>
                            <span>&rarr;</span>
                        </a>

                        @if(in_array($nextSession->status, ['ready', 'open', 'full']) && $nextSession->confirmed_slots_count > 0)
                            <button type="button"
                                    wire:click="startGameSession({{ $nextSession->id }})"
                                    wire:confirm="Mulai sesi pertempuran ini sekarang?"
                                    class="px-4 py-2 rounded-xl text-xs font-mono font-bold text-black bg-gradient-to-r from-cyan-400 to-blue-400 hover:from-cyan-300 hover:to-blue-300 transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                                ▶️ Mulai Sesi Ini
                            </button>
                        @endif
                    </div>
                @else
                    <!-- No Next Session -->
                    <div class="py-8 text-center flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500 text-xl mb-3">
                            ✅
                        </div>
                        <h4 class="font-orbitron font-bold text-slate-200 text-sm">Tidak Ada Sesi Terdekat</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                            Seluruh jadwal sesi untuk hari ini telah selesai atau belum ada sesi masa depan yang dibuat.
                        </p>
                    </div>
                @endif
            </div>

        </div>

        <!-- BOTTOM OPERATIONAL STATS STRIP -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800/80">
                <span class="text-[11px] font-mono text-slate-400 block mb-0.5">Jadwal Sesi Hari Ini</span>
                <div class="font-orbitron font-extrabold text-lg text-white">
                    {{ $totalSessionsToday }} <span class="text-xs font-normal text-slate-400">Sesi</span>
                </div>
                <span class="text-[10px] text-slate-500 font-mono">{{ $completedToday }} selesai / {{ $playingCount }} aktif</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800/80">
                <span class="text-[11px] font-mono text-slate-400 block mb-0.5">Pemain Terkonfirmasi</span>
                <div class="font-orbitron font-extrabold text-lg text-cyan-400">
                    {{ $confirmedPlayersToday }} <span class="text-xs font-normal text-slate-400">/ {{ $maxCapacityToday }}</span>
                </div>
                <span class="text-[10px] text-cyan-500 font-mono">{{ $occupancyPercent }}% Kapasitas Terisi</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800/80">
                <span class="text-[11px] font-mono text-slate-400 block mb-0.5">Sesi Berlangsung</span>
                <div class="font-orbitron font-extrabold text-lg {{ $playingCount > 0 ? 'text-amber-400 animate-pulse' : 'text-slate-300' }}">
                    {{ $playingCount }} <span class="text-xs font-normal text-slate-400">Sesi Aktif</span>
                </div>
                <span class="text-[10px] text-slate-500 font-mono">{{ $totalSessionsToday - $completedToday - $playingCount }} Sesi Mendatang</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800/80">
                <span class="text-[11px] font-mono text-slate-400 block mb-0.5">Pendapatan Hari Ini</span>
                <div class="font-orbitron font-extrabold text-lg text-emerald-400">
                    Rp{{ number_format($revenueToday, 0, ',', '.') }}
                </div>
                <span class="text-[10px] text-emerald-500/80 font-mono">Transaksi Terverifikasi</span>
            </div>
        </div>

        <!-- QR CAMERA SCANNER & CHECK-IN MODAL -->
        @if($showQrModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
                 x-data="{
                    scannerStarted: false,
                    html5QrCode: null,
                    activeCamera: null,
                    cameras: [],
                    initScanner() {
                        const loadScript = () => {
                            if (window.Html5Qrcode) {
                                this.startCamera();
                                return;
                            }
                            const s = document.createElement('script');
                            s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                            s.onload = () => this.startCamera();
                            document.head.appendChild(s);
                        };
                        loadScript();
                    },
                    startCamera() {
                        if (!window.Html5Qrcode) return;
                        if (this.html5QrCode) {
                            try { this.html5QrCode.stop(); } catch (e) {}
                        }
                        this.html5QrCode = new Html5Qrcode('qr-reader-container');
                        Html5Qrcode.getCameras().then(devices => {
                            if (devices && devices.length) {
                                this.cameras = devices;
                                const backCamera = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('environment')) || devices[0];
                                this.activeCamera = backCamera.id;
                                this.html5QrCode.start(
                                    backCamera.id,
                                    { fps: 15, qrbox: { width: 260, height: 260 } },
                                    (decodedText) => {
                                        this.playBeep();
                                        $wire.processCheckIn(decodedText);
                                    },
                                    (err) => {}
                                ).then(() => {
                                    this.scannerStarted = true;
                                }).catch(err => {
                                    console.warn('Camera start error:', err);
                                });
                            }
                        }).catch(err => {
                            console.warn('Unable to get cameras:', err);
                        });
                    },
                    stopCamera() {
                        if (this.html5QrCode) {
                            try {
                                this.html5QrCode.stop().then(() => this.html5QrCode.clear());
                            } catch (e) {}
                            this.scannerStarted = false;
                        }
                    },
                    playBeep() {
                        try {
                            const ctx = new (window.AudioContext || window.webkitAudioContext)();
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.value = 880;
                            gain.gain.value = 0.15;
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.12);
                        } catch(e) {}
                    }
                 }"
                 x-init="initScanner()"
                 @keydown.escape.window="stopCamera(); $wire.closeQrModal()">

                <div class="relative w-full max-w-lg rounded-3xl bg-[#090d16] border border-cyan-500/50 p-6 shadow-[0_0_50px_rgba(6,182,212,0.3)] space-y-5"
                     @click.outside="stopCamera(); $wire.closeQrModal()">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">📷</span>
                            <div>
                                <h3 class="font-orbitron font-bold text-base text-white">Scan QR Tiket & Check-In</h3>
                                <p class="text-[11px] text-slate-400">Arahkan kamera ke QR code tiket pemain</p>
                            </div>
                        </div>
                        <button type="button" 
                                @click="stopCamera(); $wire.closeQrModal()"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                            ✕
                        </button>
                    </div>

                    <!-- CAMERA SCANNER VIEWPORT -->
                    <div class="relative rounded-2xl overflow-hidden border-2 border-dashed border-cyan-500/40 bg-black min-h-[280px] flex items-center justify-center">
                        <div id="qr-reader-container" class="w-full h-full min-h-[280px]"></div>

                        <!-- Target Overlay Box when camera active -->
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                            <div class="w-56 h-56 border-2 border-cyan-400 rounded-2xl relative shadow-[0_0_20px_rgba(6,182,212,0.4)]">
                                <div class="absolute top-0 left-0 w-4 h-4 border-t-4 border-l-4 border-cyan-300 -mt-1 -ml-1"></div>
                                <div class="absolute top-0 right-0 w-4 h-4 border-t-4 border-r-4 border-cyan-300 -mt-1 -mr-1"></div>
                                <div class="absolute bottom-0 left-0 w-4 h-4 border-b-4 border-l-4 border-cyan-300 -mb-1 -ml-1"></div>
                                <div class="absolute bottom-0 right-0 w-4 h-4 border-b-4 border-r-4 border-cyan-300 -mb-1 -mr-1"></div>

                                <!-- Animated Laser Scanning Bar -->
                                <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-red-500 to-transparent shadow-[0_0_10px_rgba(239,68,68,0.8)] animate-bounce mt-24"></div>
                            </div>
                        </div>
                    </div>

                    <!-- FEEDBACK HASIL CHECK-IN TERAKHIR -->
                    @if($lastCheckIn)
                        @if($lastCheckIn['status'] === 'success')
                            <div class="p-4 rounded-2xl bg-emerald-950/80 border border-emerald-500 shadow-[0_0_25px_rgba(16,185,129,0.3)] animate-pulse">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-black flex items-center justify-center font-bold text-xl">
                                        ✓
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-orbitron font-extrabold text-sm text-emerald-300">
                                            CHECK-IN BERHASIL!
                                        </div>
                                        <div class="text-xs text-white font-bold mt-0.5">
                                            {{ $lastCheckIn['player_name'] }}
                                        </div>
                                        <div class="text-[11px] text-slate-300 font-mono mt-0.5">
                                            Tim: <span class="font-bold {{ $lastCheckIn['team_color'] === 'red' ? 'text-red-400' : 'text-blue-400' }}">{{ $lastCheckIn['team_name'] }}</span>
                                            &bull; Booking: {{ $lastCheckIn['booking_code'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-2xl bg-red-950/80 border border-red-500 text-xs text-red-200 flex items-center gap-3">
                                <span class="text-xl">⚠️</span>
                                <div>
                                    <div class="font-bold text-red-300">Gagal Melakukan Check-In</div>
                                    <div>{{ $lastCheckIn['message'] }}</div>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- MANUAL INPUT OR FILE FALLBACK -->
                    <form wire:submit.prevent="processCheckIn()" class="pt-2 border-t border-slate-800 space-y-3">
                        <div class="text-[11px] font-mono text-slate-400">
                            Atau input token QR / kode booking secara manual:
                        </div>
                        <div class="flex gap-2">
                            <input type="text" 
                                   wire:model="manualToken" 
                                   placeholder="Contoh: TKN-ABCD-1234 atau BOOK-202610..." 
                                   class="flex-1 px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-cyan-400" />
                            <button type="submit" 
                                    class="px-5 py-2.5 rounded-xl font-mono font-bold text-xs bg-cyan-500 text-black hover:bg-cyan-400 transition-all cursor-pointer">
                                Check-In
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        @endif

    </div>
</x-filament-widgets::widget>

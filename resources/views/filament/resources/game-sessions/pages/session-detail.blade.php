<x-filament-panels::page>
    @php
        $session = $this->record;
        $session->loadMissing(['teams.bookingPlayers.booking.payments', 'teams.bookingPlayers.playerProfile', 'waitlists']);
        $redTeam = $session->redTeam;
        $blueTeam = $session->blueTeam;
        $isPlaying = $session->isPlaying();
        $isCompleted = $session->isCompleted();
        $isCancelled = $session->isCancelled();
        $remainingSeconds = $session->getRemainingSeconds();
    @endphp

    <div class="space-y-6">

        <!-- 1. Header Overview Section -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xl font-bold tracking-tight text-primary-500 font-mono">{{ $session->session_code }}</span>
                    @if($isPlaying)
                        <x-filament::badge color="danger">BERMAIN (LIVE)</x-filament::badge>
                    @elseif($isCompleted)
                        <x-filament::badge color="gray">SELESAI</x-filament::badge>
                    @elseif($isCancelled)
                        <x-filament::badge color="danger">DIBATALKAN</x-filament::badge>
                    @elseif($session->isReady())
                        <x-filament::badge color="info">SIAP TEMPUR</x-filament::badge>
                    @elseif($session->isFull())
                        <x-filament::badge color="warning">PENUH</x-filament::badge>
                    @else
                        <x-filament::badge color="success">OPEN</x-filament::badge>
                    @endif
                </div>
            </x-slot>

            <x-slot name="description">
                {{ $session->date->isoFormat('dddd, D MMMM Y') }} &bull; Durasi Sesi: {{ $session->duration_minutes }} Menit
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Jam Permainan</span>
                    <span class="text-xl font-bold font-mono text-gray-950 dark:text-white">{{ $session->formatted_time_range }} WIB</span>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Total Pemain</span>
                    <span class="text-xl font-bold text-gray-950 dark:text-white">{{ $session->occupied_slots_count }} / {{ $session->max_players }}</span>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Slot Kosong</span>
                    <span class="text-xl font-bold text-success-600 dark:text-success-400">{{ $session->available_slots_count }}</span>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Total Pendapatan</span>
                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">Rp{{ number_format($session->confirmed_revenue, 0, ',', '.') }}</span>
                </div>
            </div>
        </x-filament::section>

        <!-- 2. Game Timer Section (Server Based) -->
        <x-filament::section
            icon="heroicon-o-clock"
            heading="Timer Permainan 30 Menit (Otoritas Server)"
            description="Waktu mulai disimpan di database server. Refresh halaman browser tidak akan me-reset timer."
        >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    @if($isPlaying)
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            </span>
                            <span class="text-sm font-semibold text-rose-500">Permainan Sedang Berlangsung</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Dimulai pada {{ $session->started_at->format('H:i:s') }} WIB
                        </p>
                    @elseif($isCompleted)
                        <span class="text-sm font-semibold text-gray-500">Sesi Telah Selesai</span>
                    @else
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status Siap: {{ $session->confirmed_slots_count }} pemain terkonfirmasi
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    @if($isPlaying)
                        <div class="text-right">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block">Sisa Waktu</span>
                            <span id="game-timer" class="text-3xl font-black font-mono text-rose-500" data-seconds="{{ $remainingSeconds }}">
                                {{ gmdate('i:s', $remainingSeconds) }}
                            </span>
                        </div>

                        <x-filament::button
                            color="danger"
                            wire:click="finishGame"
                            wire:confirm="Selesaikan sesi permainan sekarang?"
                            icon="heroicon-o-stop"
                        >
                            Akhiri Permainan
                        </x-filament::button>
                    @elseif(!$isCompleted && !$isCancelled)
                        <x-filament::button
                            color="success"
                            size="lg"
                            wire:click="startGame"
                            wire:confirm="Mulai pertempuran 30 menit sekarang?"
                            icon="heroicon-o-play"
                            :disabled="$session->confirmed_slots_count < 1"
                        >
                            Mulai Permainan Sekarang
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        <!-- 3. Actions Toolbar: Quick Check-in & Walk-in -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Scan QR / Check-In Cepat -->
            <x-filament::section icon="heroicon-o-qr-code" heading="Check-In Pemain (Scan QR / Token)">
                <div class="space-y-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Scan barcode/QR code tiket atau ketik kode token tiket pemain.</p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    type="text"
                                    wire:model="manualCheckInToken"
                                    placeholder="CHK-..."
                                    class="font-mono text-sm"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button color="primary" wire:click="checkInByToken">
                            Check-In
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <!-- Walk-in Form -->
            <x-filament::section icon="heroicon-o-user-plus" heading="Tambah Pemain Walk-In">
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model="walkInName" placeholder="Nama / Callsign *" />
                        </x-filament::input.wrapper>

                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model="walkInPhone" placeholder="WhatsApp (08...)" />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <select wire:model="walkInTeamId" class="w-full text-xs rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white py-2 px-3">
                                <option value="">Pilih Tim Otomatis</option>
                                @foreach($session->teams as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} (Sisa {{ $t->available_slots_count }})</option>
                                @endforeach
                            </select>
                        </div>
                        <x-filament::button color="success" wire:click="addWalkInPlayer">
                            + Tambah Walk-In
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- 4. Roster Tim Merah vs Tim Biru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Tim Merah -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-rose-500 font-bold text-lg">
                            <span class="w-3.5 h-3.5 rounded-full bg-rose-500"></span>
                            {{ $redTeam?->name ?? 'Tim Merah' }}
                        </span>
                        <x-filament::badge color="danger">
                            {{ $redTeam?->occupied_slots_count ?? 0 }} / {{ $redTeam?->max_players ?? 4 }} Pemain
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @for($slot = 0; $slot < ($redTeam?->max_players ?? 4); $slot++)
                        @php
                            $player = $redTeam?->activeBookingPlayers[$slot] ?? null;
                            $payment = $player?->booking?->latestPayment;
                            $isPaid = $payment?->isPaid();
                            $isChecked = $player?->isCheckedIn();
                        @endphp

                        @if($player)
                            <div class="p-3.5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 flex items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $player->player_name }}</span>
                                        @if($isChecked)
                                            <x-filament::badge color="success" size="xs">CHECKED IN</x-filament::badge>
                                        @else
                                            <x-filament::badge color="warning" size="xs">BELUM CHECK-IN</x-filament::badge>
                                        @endif

                                        @if($isPaid)
                                            <x-filament::badge color="info" size="xs">LUNAS</x-filament::badge>
                                        @else
                                            <x-filament::badge color="danger" size="xs">BELUM BAYAR</x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', $player->player_phone) }}" target="_blank" class="hover:underline text-success-600 dark:text-success-400">
                                            WA: {{ $player->player_phone }}
                                        </a>
                                        <span>&bull; {{ $player->booking->booking_code }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    @if(!$isChecked)
                                        <x-filament::button size="xs" color="success" wire:click="checkInPlayer({{ $player->id }})">
                                            ✓ Check-In
                                        </x-filament::button>
                                    @endif

                                    @if(!$isPaid && $payment)
                                        <x-filament::button size="xs" color="info" wire:click="verifyPlayerPayment({{ $payment->id }})">
                                            $ Lunas
                                        </x-filament::button>
                                    @endif

                                    @if($blueTeam && $blueTeam->available_slots_count > 0 && !$isPlaying && !$isCompleted)
                                        <x-filament::button size="xs" color="gray" wire:click="movePlayerToTeam({{ $player->id }}, {{ $blueTeam->id }})">
                                            ⇄ Biru
                                        </x-filament::button>
                                    @endif

                                    @if(!$isPlaying && !$isCompleted)
                                        <x-filament::button size="xs" color="danger" wire:click="removePlayer({{ $player->id }})" wire:confirm="Batalkan slot pemain ini?">
                                            ✕
                                        </x-filament::button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-3.5 rounded-xl border border-dashed border-gray-300 dark:border-white/10 text-center text-xs text-gray-400">
                                Slot Kosong #{{ $slot + 1 }}
                            </div>
                        @endif
                    @endfor
                </div>
            </x-filament::section>

            <!-- Tim Biru -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-blue-500 font-bold text-lg">
                            <span class="w-3.5 h-3.5 rounded-full bg-blue-500"></span>
                            {{ $blueTeam?->name ?? 'Tim Biru' }}
                        </span>
                        <x-filament::badge color="info">
                            {{ $blueTeam?->occupied_slots_count ?? 0 }} / {{ $blueTeam?->max_players ?? 4 }} Pemain
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @for($slot = 0; $slot < ($blueTeam?->max_players ?? 4); $slot++)
                        @php
                            $player = $blueTeam?->activeBookingPlayers[$slot] ?? null;
                            $payment = $player?->booking?->latestPayment;
                            $isPaid = $payment?->isPaid();
                            $isChecked = $player?->isCheckedIn();
                        @endphp

                        @if($player)
                            <div class="p-3.5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 flex items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $player->player_name }}</span>
                                        @if($isChecked)
                                            <x-filament::badge color="success" size="xs">CHECKED IN</x-filament::badge>
                                        @else
                                            <x-filament::badge color="warning" size="xs">BELUM CHECK-IN</x-filament::badge>
                                        @endif

                                        @if($isPaid)
                                            <x-filament::badge color="info" size="xs">LUNAS</x-filament::badge>
                                        @else
                                            <x-filament::badge color="danger" size="xs">BELUM BAYAR</x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', $player->player_phone) }}" target="_blank" class="hover:underline text-success-600 dark:text-success-400">
                                            WA: {{ $player->player_phone }}
                                        </a>
                                        <span>&bull; {{ $player->booking->booking_code }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    @if(!$isChecked)
                                        <x-filament::button size="xs" color="success" wire:click="checkInPlayer({{ $player->id }})">
                                            ✓ Check-In
                                        </x-filament::button>
                                    @endif

                                    @if(!$isPaid && $payment)
                                        <x-filament::button size="xs" color="info" wire:click="verifyPlayerPayment({{ $payment->id }})">
                                            $ Lunas
                                        </x-filament::button>
                                    @endif

                                    @if($redTeam && $redTeam->available_slots_count > 0 && !$isPlaying && !$isCompleted)
                                        <x-filament::button size="xs" color="gray" wire:click="movePlayerToTeam({{ $player->id }}, {{ $redTeam->id }})">
                                            ⇄ Merah
                                        </x-filament::button>
                                    @endif

                                    @if(!$isPlaying && !$isCompleted)
                                        <x-filament::button size="xs" color="danger" wire:click="removePlayer({{ $player->id }})" wire:confirm="Batalkan slot pemain ini?">
                                            ✕
                                        </x-filament::button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-3.5 rounded-xl border border-dashed border-gray-300 dark:border-white/10 text-center text-xs text-gray-400">
                                Slot Kosong #{{ $slot + 1 }}
                            </div>
                        @endif
                    @endfor
                </div>
            </x-filament::section>

        </div>

    </div>

    <!-- Live Timer Sync -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timerEl = document.getElementById('game-timer');
            if (!timerEl) return;

            let seconds = parseInt(timerEl.getAttribute('data-seconds'), 10) || 0;

            function render() {
                if (seconds <= 0) {
                    timerEl.textContent = '00:00';
                    return;
                }
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                seconds--;
            }

            render();
            setInterval(function () {
                if (seconds > 0) {
                    render();
                }
            }, 1000);
        });
    </script>
</x-filament-panels::page>

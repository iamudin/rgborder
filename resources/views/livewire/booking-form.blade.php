<div x-data="{
    bookingType: @entangle('bookingType').live,
    targetTeamId: @entangle('targetTeamId').live,
    paymentMethod: @entangle('paymentMethod'),
    basePrice: {{ (int) $session->price_per_player }},
    additionalCount: {{ count($additionalPlayers) }},
    get totalPlayers() {
        if (this.bookingType === 'individual') return 1;
        if (this.bookingType === 'full_team') return 4;
        return 1 + this.additionalCount;
    },
    get totalAmount() {
        return this.totalPlayers * this.basePrice;
    },
    formatRupiah(amount) {
        return 'Rp' + (amount || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
}" x-effect="additionalCount = {{ count($additionalPlayers) }}">
    @if ($errorMessage)
        <div class="mb-6 p-4 rounded-xl bg-red-950/80 border border-red-500/50 text-red-200 flex items-start gap-3 shadow-[0_0_20px_rgba(239,68,68,0.25)]">
            <span class="text-xl">⚠️</span>
            <div>
                <p class="font-bold text-sm">Gagal Melakukan Booking</p>
                <p class="text-xs text-red-300 mt-0.5">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="submitBooking" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left 2 Cols: Form Steps -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Step 1: Tipe Booking -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl relative overflow-hidden">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-7 h-7 rounded-lg bg-cyan-950 border border-cyan-500/50 flex items-center justify-center font-orbitron font-bold text-cyan-400 text-xs">1</span>
                    <h3 class="font-orbitron font-bold text-base text-white uppercase tracking-wider">Pilih Jenis Booking</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Individual -->
                    <label class="cursor-pointer">
                        <input type="radio" wire:model.live="bookingType" value="individual" class="peer sr-only">
                        <div class="p-4 rounded-xl border border-slate-700/80 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/20 peer-checked:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all h-full flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg">👤</span>
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300">1 Orang</span>
                                </div>
                                <h4 class="font-bold text-sm text-white">Daftar Sendiri</h4>
                                <p class="text-[11px] text-slate-400 mt-1">Bermain solo dan bergabung dengan pemain lain di arena.</p>
                            </div>
                            <span class="mt-3 font-orbitron font-bold text-xs text-cyan-400">Rp{{ number_format($session->price_per_player, 0, ',', '.') }}</span>
                        </div>
                    </label>

                    <!-- Kelompok -->
                    <label class="cursor-pointer">
                        <input type="radio" wire:model.live="bookingType" value="group" class="peer sr-only">
                        <div class="p-4 rounded-xl border border-slate-700/80 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/20 peer-checked:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all h-full flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg">👥</span>
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300">2 - 4 Orang</span>
                                </div>
                                <h4 class="font-bold text-sm text-white">Kelompok Teman</h4>
                                <p class="text-[11px] text-slate-400 mt-1">Daftar bareng teman, pasti disatukan dalam satu tim yang sama.</p>
                            </div>
                            <span class="mt-3 font-orbitron font-bold text-xs text-cyan-400">Rp{{ number_format($session->price_per_player, 0, ',', '.') }}/org</span>
                        </div>
                    </label>

                    <!-- Tim Penuh -->
                    <label class="cursor-pointer">
                        <input type="radio" wire:model.live="bookingType" value="full_team" class="peer sr-only">
                        <div class="p-4 rounded-xl border border-slate-700/80 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/20 peer-checked:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all h-full flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg">🛡️</span>
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-900/60 text-purple-300">4 Slot Tim</span>
                                </div>
                                <h4 class="font-bold text-sm text-white">Satu Tim Penuh</h4>
                                <p class="text-[11px] text-slate-400 mt-1">Amankan 1 tim lengkap untuk skuad Anda bertempur melawan tim lawan.</p>
                            </div>
                            <span class="mt-3 font-orbitron font-bold text-xs text-purple-400">Rp{{ number_format($session->price_per_player * 4, 0, ',', '.') }}</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Step 2: Pemilihan Tim -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-lg bg-cyan-950 border border-cyan-500/50 flex items-center justify-center font-orbitron font-bold text-cyan-400 text-xs">2</span>
                        <h3 class="font-orbitron font-bold text-base text-white uppercase tracking-wider">Pilih Tim</h3>
                    </div>
                    <span class="text-xs text-slate-400">Kapasitas tim: 4 orang</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Otomatis Seimbang -->
                    <label class="cursor-pointer">
                        <input type="radio" wire:model.live="targetTeamId" :value="null" class="peer sr-only">
                        <div class="p-4 rounded-xl border border-slate-700/80 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/20 peer-checked:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-sm text-white">Otomatis Seimbang</span>
                                <span class="text-xs">⚡</span>
                            </div>
                            <p class="text-[11px] text-slate-400">Sistem otomatis menempatkan Anda di tim yang paling membutuhkan pemain.</p>
                        </div>
                    </label>

                    @foreach($session->teams as $team)
                        @php
                            $isRed = $team->color === 'red';
                            $borderColor = $isRed ? 'peer-checked:border-red-500 peer-checked:bg-red-950/20 peer-checked:shadow-[0_0_15px_rgba(239,68,68,0.3)]' : 'peer-checked:border-blue-500 peer-checked:bg-blue-950/20 peer-checked:shadow-[0_0_15px_rgba(59,130,246,0.3)]';
                            $available = $team->available_slots_count;
                        @endphp
                        <label class="cursor-pointer {{ $available < $this->totalPlayers ? 'opacity-50 pointer-events-none' : '' }}">
                            <input type="radio" wire:model.live="targetTeamId" value="{{ $team->id }}" class="peer sr-only" {{ $available < $this->totalPlayers ? 'disabled' : '' }}>
                            <div class="p-4 rounded-xl border border-slate-700/80 {{ $borderColor }} transition-all">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-sm {{ $isRed ? 'text-red-400' : 'text-blue-400' }}">{{ $team->name }}</span>
                                    <span class="w-2.5 h-2.5 rounded-full {{ $isRed ? 'bg-red-500' : 'bg-blue-500' }}"></span>
                                </div>
                                <div class="text-[11px] text-slate-300 flex items-center justify-between mt-2">
                                    <span>Tersisa:</span>
                                    <span class="font-bold {{ $available > 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $available }}/{{ $team->max_players }} Slot</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Step 3: Informasi Pemain -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-7 h-7 rounded-lg bg-cyan-950 border border-cyan-500/50 flex items-center justify-center font-orbitron font-bold text-cyan-400 text-xs">3</span>
                    <h3 class="font-orbitron font-bold text-base text-white uppercase tracking-wider">Identitas Pemain</h3>
                </div>

                <!-- Pemain Utama -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-cyan-400">Pemain #1 (Pemesan)</span>
                        <span class="text-[11px] text-slate-400">Terhubung ke Akun</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Nama Pemain / Callsign <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="primaryName" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-cyan-400 focus:outline-none" placeholder="e.g. Maverick">
                            @error('primaryName') <span class="text-[11px] text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Nomor WhatsApp Aktif <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="primaryPhone" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-cyan-400 focus:outline-none" placeholder="081234567890">
                            @error('primaryPhone') <span class="text-[11px] text-red-400">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Pemain Tambahan (Jika Kelompok atau Tim Penuh) -->
                @if($bookingType !== 'individual')
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Daftar Anggota Tim (Total: <span x-text="totalPlayers"></span> Pemain)</span>
                            @if($bookingType === 'group' && count($additionalPlayers) < 3)
                                <button type="button" wire:click="addGroupPlayer" wire:loading.attr="disabled" class="px-2.5 py-1 text-xs font-semibold rounded bg-cyan-950 border border-cyan-500/50 text-cyan-300 hover:bg-cyan-900 transition-colors flex items-center gap-1">
                                    <span>+ Tambah Pemain</span>
                                </button>
                            @endif
                        </div>

                        @foreach($additionalPlayers as $index => $player)
                            <div class="p-4 rounded-xl bg-slate-900/40 border border-slate-800/80 space-y-3 relative group">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-400">Pemain #{{ $index + 2 }}</span>
                                    @if($bookingType === 'group' && count($additionalPlayers) > 1)
                                        <button type="button" wire:click="removeGroupPlayer({{ $index }})" wire:loading.attr="disabled" class="text-xs text-red-400 hover:text-red-300">Hapus</button>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-400 mb-1">Nama / Nickname <span class="text-red-400">*</span></label>
                                        <input type="text" wire:model="additionalPlayers.{{ $index }}.name" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-cyan-400 focus:outline-none" placeholder="Nama teman">
                                        @error("additionalPlayers.{$index}.name") <span class="text-[11px] text-red-400">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-400 mb-1">WhatsApp <span class="text-red-400">*</span></label>
                                        <input type="text" wire:model="additionalPlayers.{{ $index }}.phone" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-sm focus:border-cyan-400 focus:outline-none" placeholder="08xxxxxxxx">
                                        @error("additionalPlayers.{$index}.phone") <span class="text-[11px] text-red-400">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Step 4: Metode Pembayaran (Client-side instant switch with Alpine) -->
            <div class="p-6 rounded-2xl bg-[#0f172a]/90 border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-7 h-7 rounded-lg bg-cyan-950 border border-cyan-500/50 flex items-center justify-center font-orbitron font-bold text-cyan-400 text-xs">4</span>
                    <h3 class="font-orbitron font-bold text-base text-white uppercase tracking-wider">Metode Pembayaran</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" wire:model="paymentMethod" x-model="paymentMethod" value="qris" class="peer sr-only">
                        <div class="p-4 rounded-xl border border-slate-700/80 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/20 peer-checked:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all">
                            <span class="text-lg">📱</span>
                            <h4 class="font-bold text-sm text-white mt-1">QRIS Arena</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Scan cepat via GoPay, OVO, Dana, BCA, dll.</p>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" wire:model="paymentMethod" x-model="paymentMethod" value="bank_transfer" class="peer sr-only">
                        <div class="p-4 rounded-xl border border-slate-700/80 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/20 peer-checked:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all">
                            <span class="text-lg">🏦</span>
                            <h4 class="font-bold text-sm text-white mt-1">Transfer Bank</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Transfer rekening {{ $bankName }} manual.</p>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" wire:model="paymentMethod" x-model="paymentMethod" value="cash_on_site" class="peer sr-only">
                        <div class="p-4 rounded-xl border border-slate-700/80 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/20 peer-checked:shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all">
                            <span class="text-lg">💵</span>
                            <h4 class="font-bold text-sm text-white mt-1">Bayar di Lokasi</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Bayar tunai di meja kasir arena sebelum main.</p>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Ringkasan Pesanan & Konfirmasi Sticky (Instant Realtime Calculation) -->
        <div class="lg:col-span-1">
            <div class="sticky top-20 p-6 rounded-2xl bg-[#0f172a]/95 border border-cyan-500/30 shadow-[0_0_25px_rgba(6,182,212,0.15)] space-y-5">
                <div class="border-b border-slate-800 pb-4">
                    <span class="text-[11px] font-mono tracking-wider text-cyan-400 uppercase font-semibold">Ringkasan Booking</span>
                    <h3 class="font-orbitron font-bold text-lg text-white mt-1">{{ $session->session_code }}</h3>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between text-slate-300">
                        <span>Tanggal Permainan:</span>
                        <span class="font-semibold text-white">{{ $session->date->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-300">
                        <span>Jam Sesi:</span>
                        <span class="font-semibold text-cyan-400 font-mono text-sm">{{ $session->formatted_time_range }} WIB</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-300">
                        <span>Durasi Pertempuran:</span>
                        <span class="font-semibold text-white">{{ $session->duration_minutes }} Menit</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-300">
                        <span>Pilihan Tim:</span>
                        <span class="font-semibold text-white" x-text="targetTeamId ? (targetTeamId == '{{ $session->redTeam?->id }}' ? '{{ $session->redTeam?->name }}' : '{{ $session->blueTeam?->name }}') : 'Otomatis Seimbang'">
                            @if($targetTeamId)
                                {{ $session->teams->firstWhere('id', $targetTeamId)?->name }}
                            @else
                                Otomatis Seimbang
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-slate-300">
                        <span>Jumlah Pemain:</span>
                        <span class="font-semibold text-white" x-text="totalPlayers + ' Pemain'">{{ $this->totalPlayers }} Pemain</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-300">
                        <span>Harga per Pemain:</span>
                        <span class="font-semibold text-white">Rp{{ number_format($session->price_per_player, 0, ',', '.') }}</span>
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-sm">
                        <span class="font-bold text-white uppercase tracking-wider">Total Tagihan:</span>
                        <span class="font-orbitron font-black text-lg text-cyan-400 glow-text-cyan" x-text="formatRupiah(totalAmount)">
                            Rp{{ number_format($this->totalAmount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Hold Notice Alert -->
                <div class="p-3 rounded-xl bg-amber-950/40 border border-amber-500/30 text-amber-200 text-[11px] leading-relaxed flex items-start gap-2">
                    <span>⏱️</span>
                    <span>Slot akan ditahan selama <strong>{{ $holdDuration }} menit</strong> setelah booking dibuat untuk menyelesaikan verifikasi pembayaran.</span>
                </div>

                <!-- Checkbox Aturan Arena -->
                <div>
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="rulesAgreed" class="mt-0.5 rounded border-slate-700 bg-slate-900 text-cyan-500 focus:ring-0">
                        <span class="text-[11px] text-slate-300">
                            Saya telah membaca dan menyetujui <a href="{{ route('home') }}#rules" target="_blank" class="text-cyan-400 underline">Peraturan Permainan RGB Arena</a> serta bersedia hadir 15 menit sebelum sesi dimulai.
                        </span>
                    </label>
                    @error('rulesAgreed') <span class="text-[11px] text-red-400 block mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Action Button -->
                <button type="submit" wire:loading.attr="disabled" class="w-full py-3.5 px-4 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-gradient-to-r from-cyan-400 to-emerald-400 hover:from-cyan-300 hover:to-emerald-300 transition-all shadow-[0_0_20px_rgba(6,182,212,0.4)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="submitBooking">Konfirmasi & Kunci Slot</span>
                    <span wire:loading wire:target="submitBooking" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses Kunci Slot...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

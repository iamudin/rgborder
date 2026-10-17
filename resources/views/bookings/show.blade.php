@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <!-- Top Navigation / Breadcrumb -->
    <div class="flex items-center justify-between">
        <a href="{{ route('my-bookings') }}" wire:navigate.hover class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-cyan-400 transition-colors">
            <span>← Kembali ke Riwayat Booking</span>
        </a>
        <span class="font-mono text-xs text-slate-400">Dibuat: {{ $booking->created_at->format('d M Y, H:i') }}</span>
    </div>

    <!-- Booking Header Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-[#0f172a]/95 border border-slate-800 shadow-2xl relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-slate-800/80 pb-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Detail Booking Arena</span>
                    @if($booking->isConfirmed())
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-950/80 border border-emerald-500/50 text-emerald-300">TERKONFIRMASI</span>
                    @elseif($booking->isPendingPayment())
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-950/80 border border-amber-500/50 text-amber-300 animate-pulse">MENUNGGU PEMBAYARAN</span>
                    @elseif($booking->isExpired())
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 border border-slate-700 text-slate-400">KEDALUWARSA</span>
                    @elseif($booking->isCancelled())
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase bg-red-950/80 border border-red-500/50 text-red-300">DIBATALKAN</span>
                    @endif
                </div>
                <h1 class="font-orbitron font-extrabold text-3xl sm:text-4xl text-white tracking-wider glow-text-cyan">
                    {{ $booking->booking_code }}
                </h1>
            </div>

            <!-- Total Amount -->
            <div class="text-left md:text-right">
                <span class="text-xs text-slate-400 uppercase tracking-wider">Total Tagihan</span>
                <div class="font-orbitron font-black text-2xl sm:text-3xl text-cyan-400">
                    Rp{{ number_format($booking->total_amount, 0, ',', '.') }}
                </div>
                <span class="text-[11px] text-slate-400">{{ $booking->total_players }} Pemain &bull; {{ $booking->formatted_booking_type }}</span>
            </div>
        </div>

        <!-- Sesi Details Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Tanggal Permainan</span>
                <span class="font-bold text-white text-sm">{{ $booking->gameSession->date->isoFormat('D MMMM Y') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Jam Pertempuran</span>
                <span class="font-mono font-bold text-cyan-400 text-sm">{{ $booking->gameSession->formatted_time_range }} WIB</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Durasi Sesi</span>
                <span class="font-bold text-white text-sm">{{ $booking->gameSession->duration_minutes }} Menit</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Tim Bertarung</span>
                <span class="font-bold text-white text-sm">
                    @if($booking->targetTeam)
                        <span class="{{ $booking->targetTeam->color === 'red' ? 'text-red-400' : 'text-blue-400' }}">
                            {{ $booking->targetTeam->name }}
                        </span>
                    @else
                        Ditentukan Sistem
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- HOLD TIMER & PAYMENT SECTION (IF PENDING) -->
    @if($booking->isPendingPayment())
        <div class="p-6 sm:p-8 rounded-3xl bg-[#0f172a]/95 border border-amber-500/40 shadow-[0_0_30px_rgba(245,158,11,0.15)] space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <h2 class="font-orbitron font-bold text-lg text-white">Batas Waktu Reservasi Slot (Temporary Hold)</h2>
                        <p class="text-xs text-slate-400">Selesaikan pembayaran sebelum waktu habis agar slot tidak dibatalkan otomatis.</p>
                    </div>
                </div>

                <!-- Live Countdown (Alpine.js reactive) -->
                <div class="px-4 py-2 rounded-xl bg-amber-950/60 border border-amber-500/50 text-center"
                     x-data="{
                        secondsLeft: {{ $booking->remaining_hold_seconds }},
                        timerText: '--:--',
                        expired: false,
                        init() {
                            this.update();
                            let interval = setInterval(() => {
                                if (this.secondsLeft <= 0) {
                                    clearInterval(interval);
                                    this.expired = true;
                                    this.timerText = '00:00 (EXPIRED)';
                                    window.location.reload();
                                } else {
                                    this.secondsLeft--;
                                    this.update();
                                }
                            }, 1000);
                        },
                        update() {
                            let m = Math.floor(Math.max(0, this.secondsLeft) / 60);
                            let s = Math.max(0, this.secondsLeft) % 60;
                            this.timerText = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                        }
                     }">
                    <span class="text-[10px] text-amber-300 font-mono block">SISA WAKTU HOLD</span>
                    <span class="font-orbitron font-black text-xl font-mono"
                          :class="expired ? 'text-red-400' : 'text-amber-400'"
                          x-text="timerText">
                        --:--
                    </span>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left: Payment Method Details -->
                <div class="space-y-4">
                    <h3 class="font-orbitron font-bold text-sm text-cyan-400 uppercase tracking-wider">Instruksi Pembayaran</h3>

                    @php
                        $payment = $booking->latestPayment;
                        $method = $payment?->payment_method ?? 'qris';
                    @endphp

                    @if($method === 'qris')
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
                            <span class="text-xs font-bold text-white block">Metode: QRIS Statis Arena</span>
                            <div class="bg-white p-3 rounded-xl inline-block">
                                <!-- Sample QR Code representation -->
                                <svg class="w-36 h-36 mx-auto" viewBox="0 0 100 100" fill="black">
                                    <rect width="100" height="100" fill="white"/>
                                    <rect x="10" y="10" width="25" height="25" fill="black"/>
                                    <rect x="15" y="15" width="15" height="15" fill="white"/>
                                    <rect x="65" y="10" width="25" height="25" fill="black"/>
                                    <rect x="70" y="15" width="15" height="15" fill="white"/>
                                    <rect x="10" y="65" width="25" height="25" fill="black"/>
                                    <rect x="15" y="70" width="15" height="15" fill="white"/>
                                    <rect x="45" y="45" width="10" height="10" fill="black"/>
                                    <rect x="40" y="20" width="8" height="8" fill="black"/>
                                    <rect x="20" y="40" width="8" height="8" fill="black"/>
                                    <rect x="65" y="65" width="15" height="15" fill="black"/>
                                </svg>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Scan QRIS di atas menggunakan aplikasi mobile banking atau e-wallet (BCA, GoPay, OVO, Dana) dengan nominal pas: <strong>Rp{{ number_format($booking->total_amount, 0, ',', '.') }}</strong>.
                            </p>
                        </div>
                    @elseif($method === 'bank_transfer')
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3 text-xs">
                            <span class="font-bold text-white block">Metode: Transfer Bank Manual</span>
                            <div class="space-y-1">
                                <span class="text-slate-400 block">Bank Tujuan:</span>
                                <span class="font-bold text-white text-sm">{{ $bankName }}</span>
                            </div>
                            <div class="space-y-1">
                                <span class="text-slate-400 block">Nomor Rekening:</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-cyan-400 text-base">{{ $bankNumber }}</span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <span class="text-slate-400 block">Atas Nama:</span>
                                <span class="font-semibold text-white">{{ $bankHolder }}</span>
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2 text-xs">
                            <span class="font-bold text-white block">Metode: Bayar di Lokasi (Cash)</span>
                            <p class="text-slate-300 leading-relaxed">
                                Silakan lakukan pembayaran tunai di kasir arena minimal 15 menit sebelum waktu permainan. Petugas arena akan memverifikasi dan mengonfirmasi booking Anda.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Right: Proof Upload Form -->
                <div class="space-y-4">
                    <h3 class="font-orbitron font-bold text-sm text-cyan-400 uppercase tracking-wider">Kirim Bukti Pembayaran</h3>

                    @if($payment?->status === 'pending_verification')
                        <div class="p-4 rounded-xl bg-cyan-950/40 border border-cyan-500/40 text-cyan-200 text-xs space-y-2">
                            <span class="font-bold block">Bukti Pembayaran Sudah Dikirim</span>
                            <p class="text-slate-300">Petugas arena sedang memeriksa bukti pembayaran Anda. Status booking akan otomatis terkonfirmasi setelah disetujui.</p>
                            @if($payment->proof_file)
                                <a href="{{ asset('storage/'.$payment->proof_file) }}" target="_blank" class="inline-block text-xs font-semibold text-cyan-400 underline mt-1">
                                    Lihat Bukti yang Telah Diunggah →
                                </a>
                            @endif
                        </div>
                    @else
                        <form action="{{ route('my-bookings.upload-proof', $booking->booking_code) }}" method="POST" enctype="multipart/form-data" class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Unggah Bukti Transfer / Struk <span class="text-red-400">*</span></label>
                                <input type="file" name="proof_file" accept="image/*" required class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-cyan-950 file:text-cyan-400 hover:file:bg-cyan-900 border border-slate-700 rounded-lg bg-slate-950">
                                @error('proof_file') <span class="text-[11px] text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-300 mb-1">Nomor Referensi / Catatan (Opsional)</label>
                                <input type="text" name="reference_number" placeholder="Contoh: No Ref BCA 123456" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-white text-xs">
                            </div>

                            <button type="submit" class="w-full py-2.5 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-cyan-400 hover:bg-cyan-300 transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                                Unggah Bukti & Verifikasi
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- DIGITAL TICKETS (TIKET DIGITAL QR) SECTION -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Tiket Pertempuran Digital</span>
                <h2 class="font-orbitron font-extrabold text-2xl text-white tracking-wide mt-1">Tiket Masuk & QR Check-In</h2>
            </div>
            <span class="text-xs text-slate-400">{{ $booking->bookingPlayers->count() }} Tiket Pemain</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($booking->bookingPlayers as $player)
                @php
                    $isCheckedIn = $player->isCheckedIn();
                    $team = $player->team;
                    $isRed = $team?->color === 'red';
                    $borderColor = $isRed ? 'border-red-500/40 shadow-[0_0_25px_rgba(239,68,68,0.15)]' : 'border-blue-500/40 shadow-[0_0_25px_rgba(59,130,246,0.15)]';
                @endphp
                <div class="p-6 rounded-3xl bg-[#0f172a]/95 border {{ $borderColor }} flex flex-col justify-between space-y-4 relative overflow-hidden">
                    <!-- Top Ticket Header -->
                    <div class="flex items-start justify-between border-b border-slate-800 pb-3">
                        <div>
                            <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase">TIKET PEMAIN</span>
                            <h3 class="font-orbitron font-extrabold text-xl text-white">{{ $player->player_name }}</h3>
                            <span class="text-xs text-slate-400">{{ $player->player_phone }}</span>
                        </div>

                        <!-- Team Badge -->
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-orbitron font-bold {{ $isRed ? 'bg-red-950/80 text-red-400 border border-red-500/40' : 'bg-blue-950/80 text-blue-400 border border-blue-500/40' }}">
                                {{ $team?->name ?? 'Tim Menunggu' }}
                            </span>
                        </div>
                    </div>

                    <!-- QR Code & Status Center -->
                    <div class="flex flex-col sm:flex-row items-center gap-6 py-2">
                        <div class="w-36 h-36 bg-white p-2 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg">
                            @if(isset($playerQrs[$player->id]))
                                <div class="w-full h-full">
                                    {!! $playerQrs[$player->id] !!}
                                </div>
                            @else
                                <div class="text-center p-2 text-slate-800 text-[10px]">
                                    QR aktif setelah booking terkonfirmasi
                                </div>
                            @endif
                        </div>

                        <div class="space-y-2 text-xs flex-1">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Status Check-In:</span>
                                @if($isCheckedIn)
                                    <span class="inline-flex items-center gap-1 font-bold text-emerald-400 text-sm mt-0.5">
                                        <span>✓ SUDAH CHECK-IN</span>
                                        <span class="text-[11px] font-normal text-slate-400">({{ $player->checked_in_at->format('H:i') }} WIB)</span>
                                    </span>
                                @else
                                    <span class="font-bold text-amber-400 text-sm mt-0.5 block">
                                        BELUM CHECK-IN
                                    </span>
                                @endif
                            </div>

                            <div class="pt-1">
                                <span class="text-slate-400 block text-[11px]">Kode Tiket Mandiri:</span>
                                <span class="font-mono font-bold text-slate-200 text-xs bg-slate-900 px-2 py-1 rounded border border-slate-800 inline-block">
                                    {{ $player->check_in_token }}
                                </span>
                            </div>

                            <p class="text-[11px] text-slate-400 leading-snug">
                                Tunjukkan QR Code ini di meja resepsionis RGB Arena saat tiba di lokasi.
                            </p>

                            <div class="pt-1">
                                <a href="{{ route('check-in.show', $player->check_in_token) }}" target="_blank" class="text-cyan-400 hover:text-cyan-300 font-mono text-[11px] inline-flex items-center gap-1 font-semibold">
                                    <span>📱 Buka Tiket Digital Layar Penuh</span>
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Ticket Details -->
                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                        <span>{{ $booking->gameSession->formatted_time_range }} WIB</span>
                        <span>{{ $booking->gameSession->date->format('d M Y') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Actions Footer -->
    <div class="pt-6 border-t border-slate-800 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('schedule') }}" wire:navigate.hover class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">
            ← Jadwal Permainan Lainnya
        </a>

        @if(!in_array($booking->status, ['cancelled', 'expired']) && !$booking->gameSession->isCompleted())
            <form action="{{ route('my-bookings.cancel', $booking->booking_code) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan booking ini? Slot yang telah dipesan akan dilepaskan ke antrean.')">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold text-red-400 hover:text-red-300 hover:bg-red-950/40 border border-red-900/60 transition-colors">
                    Batalkan Booking Ini
                </button>
            </form>
        @endif
    </div>

</div>
@endsection

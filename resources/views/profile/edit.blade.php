@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div>
        <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Akun Pelanggan</span>
        <h1 class="font-orbitron font-extrabold text-2xl sm:text-3xl text-white tracking-wide mt-1">
            Profil Pemain
        </h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Pastikan nomor WhatsApp Anda aktif untuk menerima tiket dan pembaruan sesi arena.
        </p>
    </div>

    <div class="p-6 sm:p-8 rounded-3xl bg-[#0f172a]/95 border border-slate-800 shadow-2xl space-y-6">
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @if(request()->filled('redirect_to'))
                <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
            @endif
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Nama Pemain / Callsign <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-cyan-400 focus:outline-none">
                @error('name') <span class="text-[11px] text-red-400">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Email (Google Account)</label>
                <input type="email" value="{{ $user->email }}" disabled class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 text-sm cursor-not-allowed">
                <span class="text-[11px] text-slate-500 mt-1 block">Email terhubung dengan autentikasi Google.</span>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Nomor WhatsApp Aktif <span class="text-red-400">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="081234567890" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-cyan-400 focus:outline-none">
                @error('phone') <span class="text-[11px] text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                @if(request()->filled('redirect_to'))
                    <a href="{{ request('redirect_to') }}" wire:navigate.hover class="text-xs text-slate-400 hover:text-white">
                        ← Kembali ke Form Order
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" wire:navigate.hover class="text-xs text-slate-400 hover:text-white">
                        ← Kembali ke Dashboard
                    </a>
                @endif
                <button type="submit" class="px-6 py-2.5 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-cyan-400 hover:bg-cyan-300 transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

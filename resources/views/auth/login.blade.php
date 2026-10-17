@extends('layouts.app')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-md w-full p-8 rounded-3xl bg-[#0f172a]/95 border border-cyan-500/30 shadow-[0_0_40px_rgba(6,182,212,0.15)] relative overflow-hidden">
        <!-- Neon decorative background circle -->
        <div class="absolute -top-16 -right-16 w-36 h-36 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-36 h-36 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center mb-8 relative z-10">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 via-purple-600 to-cyan-400 p-[2px] mb-4 shadow-[0_0_25px_rgba(6,182,212,0.4)]">
                <div class="w-full h-full bg-[#090d16] rounded-[14px] flex items-center justify-center">
                    <span class="font-orbitron font-black text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-cyan-300 text-xl">RGB</span>
                </div>
            </div>
            <h2 class="font-orbitron font-extrabold text-2xl text-white tracking-wide">MASUK KE RGB ARENA</h2>
            <p class="text-xs text-slate-400 mt-2">Masuk untuk memesan tiket, bergabung ke tim pertempuran, dan mengakses tiket digital QR.</p>
        </div>

        <div class="space-y-4 relative z-10">
            <!-- Google Login Button -->
            <a href="{{ route('auth.google') }}" class="w-full py-3.5 px-4 rounded-xl font-medium text-xs sm:text-sm text-slate-100 bg-slate-900 border border-slate-700 hover:border-cyan-400 hover:bg-slate-800 transition-all flex items-center justify-center gap-3 shadow-md group">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span class="font-semibold">Lanjutkan dengan Google</span>
            </a>

            <!-- Local Development Quick Access -->
            @if(app()->environment('local'))
                <div class="pt-4 border-t border-slate-800">
                    <div class="p-4 rounded-xl bg-cyan-950/30 border border-cyan-800/40 text-center space-y-2">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-cyan-900/60 text-cyan-300">
                            MODE PENGEMBANGAN (DEV)
                        </span>
                        <p class="text-[11px] text-slate-300">Masuk instan tanpa konfigurasi OAuth Google:</p>
                        <form action="{{ route('auth.dev-login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="email" value="player@rgbarena.com">
                            <input type="hidden" name="name" value="CyberStriker">
                            <button type="submit" class="w-full py-2.5 px-3 rounded-lg text-xs font-bold uppercase tracking-wider text-black bg-cyan-400 hover:bg-cyan-300 transition-all shadow-[0_0_12px_rgba(6,182,212,0.4)]">
                                Masuk Cepat sebagai Demo Player
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <p class="text-[11px] text-slate-400 text-center mt-6">
                Dengan melanjutkan, Anda menyetujui ketentuan layanan dan kebijakan privasi data pemain RGB Arena.
            </p>
        </div>
    </div>
</div>
@endsection

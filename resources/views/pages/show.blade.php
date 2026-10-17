@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('home') }}" wire:navigate.hover class="hover:text-cyan-400 transition-colors">Beranda</a>
        <span>/</span>
        <span class="text-slate-500">Informasi</span>
        <span>/</span>
        <span class="text-cyan-300 font-medium truncate">{{ $page->title }}</span>
    </nav>

    <!-- Page Header / Hero -->
    <div class="relative rounded-3xl bg-[#0f172a]/95 border border-slate-800 overflow-hidden shadow-2xl">
        @if($page->featured_image)
            <div class="relative h-56 sm:h-72 w-full overflow-hidden">
                <img src="{{ str_starts_with($page->featured_image, 'http') || str_starts_with($page->featured_image, '/') ? $page->featured_image : asset($page->featured_image) }}" 
                     alt="{{ $page->title }}" 
                     class="w-full h-full object-cover">
                <!-- Hexagonal Steel Mesh Overlay on Header -->
                <div class="absolute inset-0 hex-steel-overlay opacity-60 mix-blend-screen pointer-events-none"></div>
                <!-- Gradient Vignette -->
                <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-[#0f172a]/70 to-transparent"></div>
            </div>
        @endif

        <div class="p-6 sm:p-10 relative z-10 {{ $page->featured_image ? '-mt-16 sm:-mt-24' : '' }}">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <span class="px-3 py-1 rounded-full text-[11px] font-orbitron font-bold uppercase tracking-wider
                    @if($page->category === 'rules') bg-amber-950/80 text-amber-300 border border-amber-500/50
                    @elseif($page->category === 'facilities') bg-cyan-950/80 text-cyan-300 border border-cyan-500/50
                    @elseif($page->category === 'policies') bg-purple-950/80 text-purple-300 border border-purple-500/50
                    @else bg-slate-800 text-slate-300 border border-slate-700 @endif">
                    {{ $page->category_label }}
                </span>
                <span class="text-xs font-mono text-slate-400">Diperbarui: {{ $page->updated_at->isoFormat('D MMMM Y') }}</span>
            </div>

            <h1 class="font-orbitron font-black text-3xl sm:text-4xl lg:text-5xl text-white tracking-wide glow-text-cyan leading-tight">
                {{ $page->title }}
            </h1>

            @if($page->excerpt)
                <p class="text-sm sm:text-base text-slate-300 mt-4 max-w-3xl leading-relaxed">
                    {{ $page->excerpt }}
                </p>
            @endif
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="p-6 sm:p-12 rounded-3xl bg-[#0f172a]/90 border border-slate-800/90 shadow-xl relative overflow-hidden">
        <!-- Subtle background cyber grid -->
        <div class="absolute inset-0 cyber-grid opacity-10 pointer-events-none"></div>

        <div class="relative z-10 text-slate-200 text-sm sm:text-base leading-relaxed space-y-5
                    [&>h2]:font-orbitron [&>h2]:font-extrabold [&>h2]:text-2xl [&>h2]:sm:text-3xl [&>h2]:text-white [&>h2]:tracking-wide [&>h2]:pt-6 [&>h2]:pb-2 [&>h2]:border-b [&>h2]:border-slate-800/80 [&>h2]:glow-text-cyan
                    [&>h3]:font-orbitron [&>h3]:font-bold [&>h3]:text-lg [&>h3]:sm:text-xl [&>h3]:text-cyan-400 [&>h3]:pt-4 [&>h3]:pb-1
                    [&>p]:text-slate-300 [&>p]:leading-relaxed
                    [&>ul]:list-disc [&>ul]:list-inside [&>ul]:space-y-2.5 [&>ul]:pl-2 [&>ul]:text-slate-300
                    [&>ol]:list-decimal [&>ol]:list-inside [&>ol]:space-y-2.5 [&>ol]:pl-2 [&>ol]:text-slate-300
                    [&>blockquote]:p-4 [&>blockquote]:rounded-xl [&>blockquote]:bg-cyan-950/20 [&>blockquote]:border-l-4 [&>blockquote]:border-cyan-400 [&>blockquote]:italic [&>blockquote]:text-cyan-200
                    [&>strong]:text-white">
            {!! $page->content !!}
        </div>
    </div>

    <!-- Other Information Pages Section -->
    @if(isset($otherPages) && $otherPages->isNotEmpty())
        <div class="space-y-4 pt-6">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">Pusat Bantuan & Panduan</span>
                    <h3 class="font-orbitron font-bold text-xl text-white mt-0.5">Halaman Informasi Lainnya</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                @foreach($otherPages as $other)
                    <a href="{{ route('pages.show', $other->slug) }}" wire:navigate.hover 
                       class="p-5 rounded-2xl bg-[#0f172a]/90 border border-slate-800 hover:border-cyan-500/60 transition-all group flex flex-col justify-between shadow-lg">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-slate-900 text-slate-300 border border-slate-800">
                                    {{ $other->category_label }}
                                </span>
                                <span class="text-cyan-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all">→</span>
                            </div>

                            <h4 class="font-orbitron font-bold text-base text-white group-hover:text-cyan-400 transition-colors">
                                {{ $other->title }}
                            </h4>

                            @if($other->excerpt)
                                <p class="text-xs text-slate-400 mt-2 line-clamp-2 leading-relaxed">
                                    {{ $other->excerpt }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Baca Selengkapnya</span>
                            <span>{{ $other->updated_at->format('d M Y') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Tactical CTA Card -->
    <div class="p-8 rounded-3xl bg-gradient-to-r from-cyan-950/60 via-[#0f172a] to-purple-950/60 border border-cyan-500/40 shadow-[0_0_30px_rgba(6,182,212,0.2)] flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div>
            <span class="text-xs font-mono font-semibold text-cyan-400 uppercase tracking-widest">SIAP MERASAKAN PERTEMPURAN?</span>
            <h3 class="font-orbitron font-extrabold text-2xl text-white mt-1">Pesan Slot Sesi Anda Sekarang</h3>
            <p class="text-xs text-slate-300 mt-1 max-w-lg leading-relaxed">
                Bergabunglah dengan skuad Tim Merah atau Tim Biru dan buktikan ketangkasan Anda di RGB Arena.
            </p>
        </div>

        <a href="{{ route('schedule') }}" wire:navigate.hover class="px-8 py-3.5 rounded-xl font-orbitron font-bold text-xs uppercase tracking-wider text-black bg-gradient-to-r from-cyan-400 via-cyan-300 to-emerald-400 hover:from-cyan-300 hover:to-emerald-300 transition-all shadow-[0_0_20px_rgba(6,182,212,0.4)] text-center flex-shrink-0">
            ⚡ Lihat Jadwal & Booking
        </a>
    </div>

</div>
@endsection

<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">

        <!-- General Arena Settings -->
        <x-filament::section heading="Informasi Umum Arena">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Arena</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="arena_name" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nomor WhatsApp Resmi Arena</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="arena_whatsapp" />
                    </x-filament::input.wrapper>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Alamat Lengkap Lokasi Arena</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="arena_address" placeholder="Contoh: Jl. Arena Perjuangan No. 88, Kebayoran Baru, Jakarta Selatan" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <!-- Media Video & Lokasi Peta Arena -->
        <x-filament::section heading="Media Video Trailer & Lokasi Peta Arena" description="Konfigurasi video YouTube aksi arena dan tautan embed Google Maps yang tampil di beranda pelanggan.">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">URL Video Trailer YouTube</label>
                    <x-filament::input.wrapper prefix="YouTube">
                        <x-filament::input type="text" wire:model="youtube_trailer_url" placeholder="https://www.youtube.com/watch?v=kXYiU_JCYtU" />
                    </x-filament::input.wrapper>
                    <span class="text-[11px] text-gray-500 mt-1 block">Mendukung format link YouTube standar (watch?v=...), youtu.be, atau ID video 11 karakter.</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">URL Embed Google Maps</label>
                    <x-filament::input.wrapper prefix="Maps">
                        <x-filament::input type="text" wire:model="google_maps_embed_url" placeholder="https://www.google.com/maps/embed?pb=..." />
                    </x-filament::input.wrapper>
                    <span class="text-[11px] text-gray-500 mt-1 block">Salin link 'src' dari fitur 'Bagikan -> Sematkan Peta' di Google Maps.</span>
                </div>
            </div>
        </x-filament::section>

        <!-- Session & Business Parameters -->
        <x-filament::section heading="Parameter Bisnis & Pertempuran" description="Aturan kapasitas tim, durasi, dan harga tiket per pemain.">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Tiket / Pemain (Rp)</label>
                    <x-filament::input.wrapper prefix="Rp">
                        <x-filament::input type="number" wire:model="price_per_player" step="1000" />
                    </x-filament::input.wrapper>
                    <span class="text-[11px] text-gray-500 mt-1 block">Default: Rp25.000 (Sesi Penuh: Rp200.000)</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Durasi Pertempuran (Menit)</label>
                    <x-filament::input.wrapper suffix="Menit">
                        <x-filament::input type="number" wire:model="session_duration_minutes" />
                    </x-filament::input.wrapper>
                    <span class="text-[11px] text-gray-500 mt-1 block">Default: 30 Menit</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jeda Persiapan Antar-Sesi (Menit)</label>
                    <x-filament::input.wrapper suffix="Menit">
                        <x-filament::input type="number" wire:model="inter_session_gap_minutes" />
                    </x-filament::input.wrapper>
                    <span class="text-[11px] text-gray-500 mt-1 block">Default: 15 Menit</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kapasitas Maksimal Per Tim</label>
                    <x-filament::input.wrapper suffix="Pemain">
                        <x-filament::input type="number" wire:model="team_capacity" />
                    </x-filament::input.wrapper>
                    <span class="text-[11px] text-gray-500 mt-1 block">Default: 4 Pemain (Total: 8 / Sesi)</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Batas Waktu Hold Pembayaran (Menit)</label>
                    <x-filament::input.wrapper suffix="Menit">
                        <x-filament::input type="number" wire:model="hold_duration_minutes" />
                    </x-filament::input.wrapper>
                    <span class="text-[11px] text-gray-500 mt-1 block">Default: 10 Menit</span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Jam Operasional (Buka - Tutup)</label>
                    <div class="flex items-center gap-2">
                        <x-filament::input.wrapper class="flex-1">
                            <x-filament::input type="text" wire:model="operational_open_time" placeholder="10:00" />
                        </x-filament::input.wrapper>
                        <span class="text-gray-400">-</span>
                        <x-filament::input.wrapper class="flex-1">
                            <x-filament::input type="text" wire:model="operational_close_time" placeholder="22:00" />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Bank & Payment Settings -->
        <x-filament::section heading="Informasi Rekening Bank Manual">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Bank</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="bank_name" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nomor Rekening</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="bank_account_number" />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Atas Nama Rekening</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model="bank_account_holder" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <!-- Hero Background Slider & Hexagonal Parallax Settings -->
        <x-filament::section heading="Background Hero Slider & Efek Parallax" description="Konfigurasi gambar latar belakang beranda dengan animasi slider dan overlay jaring baja hexagonal.">
            <div class="space-y-6">
                <!-- Toggle Slider -->
                <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-800">
                    <input type="checkbox" id="hero_slider_enabled" wire:model="hero_slider_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4">
                    <label for="hero_slider_enabled" class="text-sm font-medium text-gray-800 dark:text-gray-200 cursor-pointer">
                        Aktifkan Background Slider Parallax pada Hero Beranda
                    </label>
                </div>

                <!-- Existing Slides List -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-3">Daftar Slide Aktif ({{ count($hero_slides) }})</h4>
                    
                    @if(empty($hero_slides))
                        <div class="p-4 text-center rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-800 text-xs text-gray-500">
                            Belum ada slide gambar hero. Tambahkan gambar baru di bawah.
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($hero_slides as $index => $slide)
                                <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-2 relative group shadow-sm">
                                    <div class="relative w-full h-32 rounded-lg overflow-hidden bg-black border border-gray-700">
                                        <img src="{{ str_starts_with($slide['image'], 'http') || str_starts_with($slide['image'], '/') ? $slide['image'] : asset($slide['image']) }}" 
                                             alt="{{ $slide['title'] ?? 'Hero Slide' }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ ($slide['badge_color'] ?? '') === 'red' ? 'bg-red-600 text-white' : 'bg-cyan-600 text-white' }}">
                                            {{ $slide['badge'] ?? 'SLIDE #' . ($index + 1) }}
                                        </div>
                                    </div>

                                    <div>
                                        <h5 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $slide['title'] ?? 'Slide #' . ($index + 1) }}</h5>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ $slide['tagline'] ?? '' }}</p>
                                    </div>

                                    <div class="pt-2 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                                        <span class="text-[10px] font-mono text-gray-400">#{{ $index + 1 }}</span>
                                        <button type="button" wire:click="removeSlide({{ $index }})" 
                                                class="px-2 py-1 text-[11px] font-semibold text-red-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition-colors">
                                            ✕ Hapus Slide
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Add New Slide Card -->
                <div class="p-5 rounded-2xl bg-gray-50/80 dark:bg-gray-900/60 border border-dashed border-gray-300 dark:border-gray-700 space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🖼️</span>
                        <div>
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white">Tambah Slide Hero Baru</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Unggah file foto arena atau masukkan URL gambar yang akan tampil di slider parallax.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- File Upload -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Unggah Gambar (JPG/PNG/WEBP, Max 5MB)</label>
                            <input type="file" wire:model="new_slide_image" accept="image/*" class="w-full text-xs text-gray-600 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-950 dark:file:text-primary-300 hover:file:bg-primary-100 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-950 p-1">
                            @error('new_slide_image') <span class="text-[11px] text-red-500 block mt-1">{{ $message }}</span> @enderror
                            
                            <!-- Temporary preview if selected -->
                            @if ($new_slide_image)
                                <div class="mt-2 relative w-full h-24 rounded-lg overflow-hidden bg-black border border-primary-500">
                                    <img src="{{ $new_slide_image->temporaryUrl() }}" class="w-full h-full object-cover">
                                    <span class="absolute bottom-1 right-1 bg-black/70 text-white text-[9px] px-1.5 py-0.5 rounded">Preview Unggahan</span>
                                </div>
                            @endif
                        </div>

                        <!-- Direct Image URL (Alternative) -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Atau Masukkan URL / Path Gambar</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="new_slide_url" placeholder="https://example.com/arena.jpg atau images/hero/..." />
                            </x-filament::input.wrapper>
                            @error('new_slide_url') <span class="text-[11px] text-red-500 block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Judul / Sektor Slide <span class="text-red-500">*</span></label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="new_slide_title" placeholder="Contoh: Zona Tempur Sektor Alpha" />
                            </x-filament::input.wrapper>
                            @error('new_slide_title') <span class="text-[11px] text-red-500 block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tagline -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tagline / Subjudul Sektor (Opsional)</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="new_slide_tagline" placeholder="Contoh: TACTICAL ARENA // ADVANCED LABYRINTH" />
                            </x-filament::input.wrapper>
                        </div>

                        <!-- Badge Label -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Teks Lencana / Badge</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="new_slide_badge" placeholder="TIM MERAH / TIM BIRU / RGB VIP" />
                            </x-filament::input.wrapper>
                        </div>

                        <!-- Badge Color -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Warna Aksen Lencana</label>
                            <select wire:model="new_slide_badge_color" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2">
                                <option value="red">Merah (Red Team)</option>
                                <option value="blue">Biru (Blue Team)</option>
                                <option value="cyan">Cyan Neon (Arena Standar)</option>
                                <option value="purple">Ungu (Squad VIP)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <x-filament::button type="button" wire:click="addSlide" color="gray" size="sm">
                            + Tambah Slide ke Hero
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <div class="flex justify-end">
            <x-filament::button type="submit" size="lg" color="primary">
                Simpan Konfigurasi Arena
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

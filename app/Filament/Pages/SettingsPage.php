<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class SettingsPage extends Page
{
    use WithFileUploads;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Arena';

    protected static ?string $title = 'Konfigurasi Arena RGB';

    protected string $view = 'filament.pages.settings-page';

    protected static ?int $navigationSort = 10;

    public string $arena_name = '';
    public int $price_per_player = 25000;
    public int $session_duration_minutes = 30;
    public int $team_capacity = 4;
    public int $teams_per_session = 2;
    public int $inter_session_gap_minutes = 15;
    public int $hold_duration_minutes = 10;
    public string $operational_open_time = '10:00';
    public string $operational_close_time = '22:00';
    public string $arena_whatsapp = '';
    public string $bank_name = 'BCA';
    public string $bank_account_number = '';
    public string $bank_account_holder = '';
    public string $arena_address = '';
    public string $youtube_trailer_url = '';
    public string $google_maps_embed_url = '';

    // Hero Background Slider Settings
    public bool $hero_slider_enabled = true;
    public array $hero_slides = [];

    // Form Tambah Slide Baru
    public $new_slide_image = null;
    public string $new_slide_url = '';
    public string $new_slide_title = '';
    public string $new_slide_tagline = '';
    public string $new_slide_badge = 'TIM MERAH';
    public string $new_slide_badge_color = 'red';

    public function mount(): void
    {
        $this->arena_name = Setting::get('arena_name', 'RGB Arena Laser Battle');
        $this->price_per_player = (int) Setting::get('price_per_player', 25000);
        $this->session_duration_minutes = (int) Setting::get('session_duration_minutes', 30);
        $this->team_capacity = (int) Setting::get('team_capacity', 4);
        $this->teams_per_session = (int) Setting::get('teams_per_session', 2);
        $this->inter_session_gap_minutes = (int) Setting::get('inter_session_gap_minutes', 15);
        $this->hold_duration_minutes = (int) Setting::get('hold_duration_minutes', 10);
        $this->operational_open_time = Setting::get('operational_open_time', '10:00');
        $this->operational_close_time = Setting::get('operational_close_time', '22:00');
        $this->arena_whatsapp = Setting::get('arena_whatsapp', '081234567890');
        $this->arena_address = Setting::get('arena_address', 'Jl. Arena Perjuangan No. 88, Kebayoran Baru, Jakarta Selatan');
        $this->youtube_trailer_url = Setting::get('youtube_trailer_url', 'https://www.youtube.com/watch?v=kXYiU_JCYtU');
        $this->google_maps_embed_url = Setting::get('google_maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126920.28299839845!2d106.759478!3d-6.229728!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e49fe3ddb3%3A0x72c5a2c0989f6b4!2sJakarta%20Selatan!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid');
        $this->bank_name = Setting::get('bank_name', 'BCA');
        $this->bank_account_number = Setting::get('bank_account_number', '8830192831');
        $this->bank_account_holder = Setting::get('bank_account_holder', 'RGB ARENA INDONESIA');

        // Hero Slider
        $this->hero_slider_enabled = (bool) Setting::get('hero_slider_enabled', true);
        $savedSlides = Setting::get('hero_slides', []);

        if (empty($savedSlides)) {
            $this->hero_slides = [
                [
                    'id' => 'slide-default-1',
                    'image' => 'images/hero/slide-1-red.jpg',
                    'title' => 'Zona Tempur Sektor Merah',
                    'tagline' => 'TACTICAL ARENA 01 // RED SQUADRON',
                    'badge' => 'TIM MERAH',
                    'badge_color' => 'red',
                ],
                [
                    'id' => 'slide-default-2',
                    'image' => 'images/hero/slide-2-blue.jpg',
                    'title' => 'Zona Tempur Sektor Biru',
                    'tagline' => 'TACTICAL ARENA 02 // BLUE CORPS',
                    'badge' => 'TIM BIRU',
                    'badge_color' => 'blue',
                ],
            ];
            Setting::set('hero_slides', $this->hero_slides, 'json', 'appearance');
        } else {
            $this->hero_slides = $savedSlides;
        }
    }

    public function addSlide(): void
    {
        $this->validate([
            'new_slide_image' => ['nullable', 'image', 'max:5120'], // Max 5MB
            'new_slide_url' => ['nullable', 'string', 'max:500'],
            'new_slide_title' => ['required', 'string', 'max:100'],
        ]);

        if (! $this->new_slide_image && empty(trim($this->new_slide_url))) {
            $this->addError('new_slide_image', 'Pilih file gambar yang diunggah atau masukkan URL gambar valid.');
            return;
        }

        $imagePath = '';
        if ($this->new_slide_image) {
            $stored = $this->new_slide_image->store('hero', 'public');
            $imagePath = 'storage/' . $stored;
        } else {
            $imagePath = trim($this->new_slide_url);
        }

        $this->hero_slides[] = [
            'id' => 'slide-' . uniqid(),
            'image' => $imagePath,
            'title' => $this->new_slide_title,
            'tagline' => $this->new_slide_tagline ?: 'SEKTOR ARENA PERTEMPURAN',
            'badge' => $this->new_slide_badge ?: 'RGB ARENA',
            'badge_color' => $this->new_slide_badge_color ?: 'cyan',
        ];

        Setting::set('hero_slides', $this->hero_slides, 'json', 'appearance');

        // Reset Form
        $this->new_slide_image = null;
        $this->new_slide_url = '';
        $this->new_slide_title = '';
        $this->new_slide_tagline = '';
        $this->new_slide_badge = 'TIM MERAH';
        $this->new_slide_badge_color = 'red';

        Notification::make()
            ->title('Slide Hero Berhasil Ditambahkan')
            ->body('Gambar arena baru telah tersimpan dan langsung tayang pada background hero beranda.')
            ->success()
            ->send();
    }

    public function removeSlide(int $index): void
    {
        if (isset($this->hero_slides[$index])) {
            array_splice($this->hero_slides, $index, 1);
            Setting::set('hero_slides', $this->hero_slides, 'json', 'appearance');

            Notification::make()
                ->title('Slide Berhasil Dihapus')
                ->body('Slide gambar telah dikeluarkan dari hero beranda.')
                ->success()
                ->send();
        }
    }

    public function save(): void
    {
        $this->validate([
            'arena_name' => ['required', 'string', 'max:100'],
            'price_per_player' => ['required', 'integer', 'min:5000'],
            'session_duration_minutes' => ['required', 'integer', 'min:10', 'max:120'],
            'team_capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'hold_duration_minutes' => ['required', 'integer', 'min:3', 'max:60'],
        ]);

        Setting::set('arena_name', $this->arena_name, 'string', 'general');
        Setting::set('price_per_player', $this->price_per_player, 'integer', 'booking');
        Setting::set('session_duration_minutes', $this->session_duration_minutes, 'integer', 'session');
        Setting::set('team_capacity', $this->team_capacity, 'integer', 'session');
        Setting::set('teams_per_session', $this->teams_per_session, 'integer', 'session');
        Setting::set('inter_session_gap_minutes', $this->inter_session_gap_minutes, 'integer', 'session');
        Setting::set('hold_duration_minutes', $this->hold_duration_minutes, 'integer', 'payment');
        Setting::set('operational_open_time', $this->operational_open_time, 'string', 'operational');
        Setting::set('operational_close_time', $this->operational_close_time, 'string', 'operational');
        Setting::set('arena_whatsapp', $this->arena_whatsapp, 'string', 'general');
        Setting::set('arena_address', $this->arena_address, 'string', 'general');
        Setting::set('youtube_trailer_url', $this->youtube_trailer_url, 'string', 'appearance');
        Setting::set('google_maps_embed_url', $this->google_maps_embed_url, 'string', 'general');
        Setting::set('bank_name', $this->bank_name, 'string', 'payment');
        Setting::set('bank_account_number', $this->bank_account_number, 'string', 'payment');
        Setting::set('bank_account_holder', $this->bank_account_holder, 'string', 'payment');

        // Hero Slider
        Setting::set('hero_slider_enabled', $this->hero_slider_enabled, 'boolean', 'appearance');
        Setting::set('hero_slides', $this->hero_slides, 'json', 'appearance');

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan')
            ->body('Konfigurasi arena dan background slider hero telah diperbarui.')
            ->success()
            ->send();
    }
}


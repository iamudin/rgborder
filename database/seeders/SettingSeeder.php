<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'arena_name',
                'value' => 'RGB Arena Laser Battle',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Nama arena permainan',
            ],
            [
                'key' => 'price_per_player',
                'value' => '25000',
                'type' => 'integer',
                'group' => 'booking',
                'description' => 'Harga tiket per pemain dalam Rupiah',
            ],
            [
                'key' => 'session_duration_minutes',
                'value' => '30',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Durasi permainan per sesi (menit)',
            ],
            [
                'key' => 'inter_session_gap_minutes',
                'value' => '15',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Jeda persiapan antar sesi (menit)',
            ],
            [
                'key' => 'team_capacity',
                'value' => '4',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Kapasitas maksimal pemain per tim',
            ],
            [
                'key' => 'teams_per_session',
                'value' => '2',
                'type' => 'integer',
                'group' => 'session',
                'description' => 'Jumlah tim dalam satu sesi',
            ],
            [
                'key' => 'hold_duration_minutes',
                'value' => '10',
                'type' => 'integer',
                'group' => 'payment',
                'description' => 'Batas waktu reservasi sementara untuk pembayaran (menit)',
            ],
            [
                'key' => 'bank_name',
                'value' => 'BCA',
                'type' => 'string',
                'group' => 'payment',
                'description' => 'Nama bank untuk transfer manual',
            ],
            [
                'key' => 'bank_account_number',
                'value' => '8830192831',
                'type' => 'string',
                'group' => 'payment',
                'description' => 'Nomor rekening bank arena',
            ],
            [
                'key' => 'bank_account_holder',
                'value' => 'RGB ARENA INDONESIA',
                'type' => 'string',
                'group' => 'payment',
                'description' => 'Nama pemilik rekening bank arena',
            ],
            [
                'key' => 'qris_image_path',
                'value' => '/images/qris-rgb-arena.svg',
                'type' => 'string',
                'group' => 'payment',
                'description' => 'Path gambar QRIS statis arena',
            ],
            [
                'key' => 'arena_whatsapp',
                'value' => '081234567890',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Nomor WhatsApp resmi arena',
            ],
            [
                'key' => 'arena_address',
                'value' => 'Jl. Arena Perjuangan No. 88, Kebayoran Baru, Jakarta Selatan',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Alamat lengkap lokasi arena',
            ],
            [
                'key' => 'youtube_trailer_url',
                'value' => 'https://www.youtube.com/watch?v=kXYiU_JCYtU',
                'type' => 'string',
                'group' => 'appearance',
                'description' => 'URL video YouTube trailer keseruan arena',
            ],
            [
                'key' => 'google_maps_embed_url',
                'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126920.28299839845!2d106.759478!3d-6.229728!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e49fe3ddb3%3A0x72c5a2c0989f6b4!2sJakarta%20Selatan!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid',
                'type' => 'string',
                'group' => 'general',
                'description' => 'URL embed iframe Google Maps lokasi arena',
            ],
            [
                'key' => 'operational_open_time',
                'value' => '10:00',
                'type' => 'string',
                'group' => 'operational',
                'description' => 'Jam buka operasional arena',
            ],
            [
                'key' => 'operational_close_time',
                'value' => '22:00',
                'type' => 'string',
                'group' => 'operational',
                'description' => 'Jam tutup operasional arena',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

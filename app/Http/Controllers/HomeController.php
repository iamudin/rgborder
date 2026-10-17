<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SessionService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(SessionService $sessionService): View
    {
        $price = Setting::get('price_per_player', 25000);
        $duration = Setting::get('session_duration_minutes', 30);
        $upcomingSessions = $sessionService->getUpcomingSessions(6);
        $heroSliderEnabled = (bool) Setting::get('hero_slider_enabled', true);
        $heroSlides = Setting::get('hero_slides', []);

        if (empty($heroSlides)) {
            $heroSlides = [
                [
                    'id' => 'default-1',
                    'image' => asset('images/hero/slide-1-red.jpg'),
                    'title' => 'Zona Tempur Sektor Merah',
                    'tagline' => 'TACTICAL ARENA 01 // RED SQUADRON',
                    'badge' => 'TIM MERAH',
                    'badge_color' => 'red',
                ],
                [
                    'id' => 'default-2',
                    'image' => asset('images/hero/slide-2-blue.jpg'),
                    'title' => 'Zona Tempur Sektor Biru',
                    'tagline' => 'TACTICAL ARENA 02 // BLUE CORPS',
                    'badge' => 'TIM BIRU',
                    'badge_color' => 'blue',
                ],
            ];
        } else {
            $heroSlides = array_map(function ($slide) {
                if (!empty($slide['image']) && !str_starts_with($slide['image'], 'http') && !str_starts_with($slide['image'], '/')) {
                    $slide['image'] = asset($slide['image']);
                }
                return $slide;
            }, $heroSlides);
        }

        // Media Trailer & Location Settings
        $youtubeTrailerUrl = Setting::get('youtube_trailer_url', 'https://www.youtube.com/watch?v=kXYiU_JCYtU');
        $youtubeEmbedUrl = $this->parseYoutubeEmbedUrl($youtubeTrailerUrl);
        $googleMapsEmbedUrl = Setting::get('google_maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126920.28299839845!2d106.759478!3d-6.229728!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e49fe3ddb3%3A0x72c5a2c0989f6b4!2sJakarta%20Selatan!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid');
        $arenaAddress = Setting::get('arena_address', 'Jl. Arena Perjuangan No. 88, Kebayoran Baru, Jakarta Selatan');
        $arenaWhatsapp = Setting::get('arena_whatsapp', '081234567890');
        $openTime = Setting::get('operational_open_time', '10:00');
        $closeTime = Setting::get('operational_close_time', '22:00');

        return view('home', compact(
            'price',
            'duration',
            'upcomingSessions',
            'heroSliderEnabled',
            'heroSlides',
            'youtubeTrailerUrl',
            'youtubeEmbedUrl',
            'googleMapsEmbedUrl',
            'arenaAddress',
            'arenaWhatsapp',
            'openTime',
            'closeTime'
        ));
    }

    protected function parseYoutubeEmbedUrl(?string $url): string
    {
        if (empty($url)) {
            return 'https://www.youtube-nocookie.com/embed/kXYiU_JCYtU?rel=0&modestbranding=1';
        }

        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $matches)) {
            return 'https://www.youtube-nocookie.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
        }

        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        return 'https://www.youtube-nocookie.com/embed/' . trim($url) . '?rel=0&modestbranding=1';
    }
}

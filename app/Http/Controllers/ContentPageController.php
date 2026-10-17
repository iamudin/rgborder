<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = ContentPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $otherPages = ContentPage::where('id', '!=', $page->id)
            ->where('is_published', true)
            ->ordered()
            ->take(4)
            ->get();

        return view('pages.show', compact('page', 'otherPages'));
    }
}

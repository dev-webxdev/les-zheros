<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        return view('pages.galerie', [
            'images' => GalleryImage::query()
                ->where('is_published', true)
                ->latest('taken_at')
                ->latest()
                ->get(),
        ]);
    }
}

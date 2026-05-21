<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(): View
    {
        return view('pages.guides', [
            'guides' => Guide::query()
                ->where('is_published', true)
                ->whereIn('category', ['donjon', 'expedition'])
                ->latest('published_at')
                ->latest()
                ->get(),
        ]);
    }

    public function show(Request $request, ?Guide $guide = null): View
    {
        $guide ??= Guide::query()
            ->where('slug', $request->query('guide'))
            ->firstOrFail();

        abort_unless($guide->is_published, 404);

        return view('pages.guide', [
            'guide' => $guide,
        ]);
    }
}

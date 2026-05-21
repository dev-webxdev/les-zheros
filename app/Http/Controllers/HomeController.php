<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('pages.index', [
            'announcements' => Announcement::query()
                ->where(function ($query): void {
                    $query
                        ->where(function ($published): void {
                            $published
                                ->where('status', 'published')
                                ->where(fn ($date) => $date->whereNull('published_at')->orWhere('published_at', '<=', now()));
                        })
                        ->orWhere(function ($scheduled): void {
                            $scheduled
                                ->where('status', 'scheduled')
                                ->where('published_at', '<=', now());
                        });
                })
                ->latest('published_at')
                ->latest()
                ->take(4)
                ->get(),
        ]);
    }
}

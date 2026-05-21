<?php

namespace App\Http\Controllers;

use App\Models\Stuff;
use Illuminate\View\View;

class StuffController extends Controller
{
    public function index(): View
    {
        $stuffs = Stuff::query()
            ->where('is_published', true)
            ->latest()
            ->get();

        return view('pages.stuffs', [
            'stuffs' => $stuffs,
        ]);
    }
}

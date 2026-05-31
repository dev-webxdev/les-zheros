<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function index(): View
    {
        return view('pages.missions', [
            'missions' => Mission::query()->latest()->get(),
        ]);
    }
}

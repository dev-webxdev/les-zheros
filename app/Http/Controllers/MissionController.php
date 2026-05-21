<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Support\MissionCycle;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function index(MissionCycle $missionCycle): View
    {
        $missionCycle->sync();

        return view('pages.missions', [
            'missions' => Mission::query()->latest()->get(),
        ]);
    }
}

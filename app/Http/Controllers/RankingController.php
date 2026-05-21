<?php

namespace App\Http\Controllers;

use App\Support\RankingBoard;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(RankingBoard $rankingBoard): View
    {
        return view('pages.classement', [
            'rankingRows' => $rankingBoard->rows(),
        ]);
    }
}

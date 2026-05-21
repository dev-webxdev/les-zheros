<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\RankingBoard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(Request $request, RankingBoard $rankingBoard): View
    {
        return view('admin.admin-ranking', [
            'rankingRows' => $this->paginateCollection($rankingBoard->rows(), $request),
        ]);
    }

    /**
     * @param Collection<int, array<string, mixed>> $items
     */
    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $perPage = 12;
        $page = max(1, (int) $request->query('page', 1));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }
}

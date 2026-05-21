<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request, ?string $area = null): View|RedirectResponse
    {
        $normalizedQuery = $request->query();
        $area = $area ?: 'all';

        if (array_key_exists('search', $normalizedQuery)) {
            $normalizedQuery['search'] = trim((string) $normalizedQuery['search']);

            if ($normalizedQuery['search'] === '') {
                unset($normalizedQuery['search']);
            }
        }

        if (array_key_exists('area', $normalizedQuery)) {
            $queryArea = (string) $normalizedQuery['area'];
            unset($normalizedQuery['area']);

            if ($queryArea !== '' && $queryArea !== 'all') {
                $area = $queryArea;
            }
        }

        if ($normalizedQuery !== $request->query() || $area === 'all' && $request->route('area')) {
            return redirect()->route('admin.activite.index', [
                'area' => $area === 'all' ? null : $area,
                ...$normalizedQuery,
            ]);
        }

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'area' => $area,
        ];
        $areas = AdminActivityLog::query()
            ->select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');
        $logs = AdminActivityLog::query()
            ->with('user')
            ->when($filters['area'] !== 'all', fn ($query) => $query->where('area', $filters['area']))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($innerQuery) use ($filters): void {
                    $innerQuery
                        ->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('description', 'like', '%'.$filters['search'].'%')
                        ->orWhere('subject_label', 'like', '%'.$filters['search'].'%')
                        ->orWhere('user_name', 'like', '%'.$filters['search'].'%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.admin-activity', [
            'logs' => $logs,
            'areas' => $areas,
            'filters' => $filters,
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $count = AdminActivityLog::query()->count();
        AdminActivityLog::query()->delete();

        return redirect()->route('admin.activite.index')->with('admin_toast', [
            'title' => 'Journal vide',
            'text' => $count > 0 ? $count.' action(s) ont ete supprimees du journal.' : 'Le journal etait deja vide.',
            'type' => 'warning',
        ]);
    }
}

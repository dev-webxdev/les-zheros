<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Support\PublicUploadManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-missions', [
            'missions' => Mission::query()
                ->with('guide')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-mission-create', [
            'mission' => new Mission(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Mission::create($this->missionPayload($request));

        return redirect()->route('admin.missions.index')->with('admin_toast', [
            'title' => 'Mission créée',
            'text' => 'Elle apparaît maintenant côté front.',
            'type' => 'success',
        ]);
    }

    public function edit(Mission $mission): View
    {
        return view('admin.admin-mission-edit', [
            'mission' => $mission,
        ]);
    }

    public function update(Request $request, Mission $mission): RedirectResponse
    {
        $mission->update($this->missionPayload($request, $mission));

        return redirect()->route('admin.missions.index')->with('admin_toast', [
            'title' => 'Mission modifiée',
            'text' => 'Les changements sont visibles côté front.',
            'type' => 'success',
        ]);
    }

    public function destroy(Mission $mission): RedirectResponse
    {
        $mission->delete();

        return redirect()->route('admin.missions.index')->with('admin_toast', [
            'title' => 'Mission en corbeille',
            'text' => 'Elle a été déplacée dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-missions-trash', [
            'missions' => Mission::onlyTrashed()->latest('deleted_at')->paginate(12),
        ]);
    }

    public function restore(int $mission): RedirectResponse
    {
        $trashedMission = Mission::onlyTrashed()->findOrFail($mission);
        $trashedMission->restore();

        return redirect()->route('admin.missions.trash')->with('admin_toast', [
            'title' => 'Mission restaurée',
            'text' => 'Elle est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $mission): RedirectResponse
    {
        $trashedMission = Mission::onlyTrashed()->findOrFail($mission);
        $trashedMission->forceDelete();

        return redirect()->route('admin.missions.trash')->with('admin_toast', [
            'title' => 'Mission supprimée',
            'text' => 'Elle a été supprimée définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        Mission::onlyTrashed()->forceDelete();

        return redirect()->route('admin.missions.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Toutes les missions en corbeille ont été supprimées définitivement.',
            'type' => 'warning',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function missionPayload(Request $request, ?Mission $mission = null): array
    {
        $validated = $request->validate([
            'title' => ['nullable', Rule::requiredIf($request->input('category') !== 'anomalie'), 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Mission::CATEGORIES))],
            'anomaly_type' => ['nullable', Rule::requiredIf($request->input('category') === 'anomalie'), Rule::in(array_keys(Mission::ANOMALY_TYPES))],
            'anomaly_level' => ['nullable', Rule::requiredIf($request->input('category') === 'anomalie'), Rule::in(Mission::ANOMALY_LEVELS)],
            'dream_type' => ['nullable', Rule::requiredIf($request->input('category') === 'songe'), Rule::in(array_keys(Mission::DREAM_TYPES))],
            'dream_floor' => ['nullable', Rule::requiredIf($request->input('category') === 'songe'), 'integer', 'between:1,5'],
            'guildatons' => ['nullable', Rule::requiredIf($request->input('category') !== 'anomalie'), 'integer', 'min:0'],
            'activity_points' => ['nullable', Rule::requiredIf($request->input('category') !== 'anomalie'), 'integer', 'min:0'],
            'image_mode' => ['nullable', Rule::in(['api', 'upload', 'url'])],
            'selected_image' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'image_files.0' => ['nullable', 'image', 'max:4096'],
            'monster_id' => ['nullable', 'string', 'max:80'],
        ]);

        $category = $validated['category'];
        $title = $category === 'anomalie'
            ? Mission::anomalyTitle($validated['anomaly_type'] ?? null, $validated['anomaly_level'] ?? null)
            : $validated['title'];
        $imagePath = $mission?->image_path;

        if ($category === 'anomalie') {
            $imagePath = null;
        } elseif ($request->hasFile('image_files.0')) {
            $imagePath = PublicUploadManager::store(
                $request->file('image_files.0'),
                'missions',
                'mission',
                name: trim($title.' '.$category),
                cleanNameOnly: true,
            );
        } elseif (($validated['image_mode'] ?? null) === 'upload' && ! empty($validated['selected_image'])) {
            $imagePath = $validated['selected_image'];
        } elseif (($validated['image_mode'] ?? null) === 'url' && ! empty($validated['image_url'])) {
            $imagePath = $validated['image_url'];
        } elseif (($validated['image_mode'] ?? null) === 'api' && ! empty($validated['selected_image'])) {
            $imagePath = $validated['selected_image'];
        }

        return [
            'title' => $title,
            'category' => $category,
            'anomaly_type' => $category === 'anomalie' ? $validated['anomaly_type'] : null,
            'anomaly_level' => $category === 'anomalie' ? $validated['anomaly_level'] : null,
            'dream_type' => $category === 'songe' ? $validated['dream_type'] : null,
            'dream_floor' => $category === 'songe' ? $validated['dream_floor'] : null,
            'guildatons' => $category === 'anomalie' ? null : $validated['guildatons'],
            'activity_points' => $category === 'anomalie' ? null : $validated['activity_points'],
            'image_mode' => $category === 'anomalie' ? null : ($validated['image_mode'] ?? null),
            'image_path' => $imagePath,
            'monster_id' => $category === 'anomalie' ? null : ($validated['monster_id'] ?? null),
        ];
    }
}

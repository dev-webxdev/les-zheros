<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stuff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StuffController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-stuffs', [
            'stuffs' => Stuff::query()->latest()->paginate(12),
            'canDeleteStuffs' => auth()->user()?->canDeleteInAdminArea('stuffs'),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-stuff-create', [
            'stuff' => new Stuff([
                'min_level' => 200,
                'max_level' => 200,
                'is_published' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Stuff::create($this->payload($request));

        return redirect()->route('admin.stuffs.index')->with('admin_toast', [
            'title' => 'Stuff créé',
            'text' => 'Le build est prêt dans le catalogue.',
            'type' => 'success',
        ]);
    }

    public function edit(Stuff $stuff): View
    {
        return view('admin.admin-stuff-create', [
            'stuff' => $stuff,
        ]);
    }

    public function update(Request $request, Stuff $stuff): RedirectResponse
    {
        $stuff->update($this->payload($request));

        return redirect()->route('admin.stuffs.index')->with('admin_toast', [
            'title' => 'Stuff modifié',
            'text' => 'Le build a bien été mis à jour.',
            'type' => 'success',
        ]);
    }

    public function destroy(Stuff $stuff): RedirectResponse
    {
        $stuff->delete();

        return redirect()->route('admin.stuffs.index')->with('admin_toast', [
            'title' => 'Stuff en corbeille',
            'text' => 'Le build a été déplacé dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:trash,restore,force_delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        if ($data['action'] === 'force_delete') {
            abort_unless($request->user()?->canForceDeleteInAdminArea('stuffs'), 403);
        } elseif ($data['action'] === 'trash') {
            abort_unless($request->user()?->canDeleteInAdminArea('stuffs'), 403);
        }

        $stuffs = $data['action'] === 'trash'
            ? Stuff::whereKey($data['ids'])->get()
            : Stuff::onlyTrashed()->whereKey($data['ids'])->get();

        if ($data['action'] === 'trash') {
            $stuffs->each->delete();
        } elseif ($data['action'] === 'restore') {
            $stuffs->each->restore();
        } else {
            $stuffs->each->forceDelete();
        }

        return back()->with('admin_toast', [
            'title' => 'Action groupée terminée',
            'text' => $stuffs->count().' stuff(s) traité(s).',
            'type' => $data['action'] === 'force_delete' ? 'warning' : 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-stuffs-trash', [
            'stuffs' => Stuff::onlyTrashed()->latest('deleted_at')->paginate(12),
        ]);
    }

    public function restore(int $stuff): RedirectResponse
    {
        Stuff::onlyTrashed()->findOrFail($stuff)->restore();

        return redirect()->route('admin.stuffs.trash')->with('admin_toast', [
            'title' => 'Stuff restauré',
            'text' => 'Le build est de retour dans le catalogue.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $stuff): RedirectResponse
    {
        Stuff::onlyTrashed()->findOrFail($stuff)->forceDelete();

        return redirect()->route('admin.stuffs.trash')->with('admin_toast', [
            'title' => 'Stuff supprimé',
            'text' => 'Le build a été supprimé définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        Stuff::onlyTrashed()->forceDelete();

        return redirect()->route('admin.stuffs.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Tous les stuffs supprimés ont été effacés.',
            'type' => 'warning',
        ]);
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'dofusbook_url' => ['required', 'url', 'max:1000'],
            'title' => ['required', 'string', 'max:255'],
            'class' => ['required', Rule::in(array_values(Stuff::CLASSES))],
            'element' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in(Stuff::MODES)],
            'meta' => ['nullable', 'string', 'max:255'],
            'min_level' => ['required', 'integer', Rule::in(Stuff::LEVELS)],
            'max_level' => ['nullable', 'integer', Rule::in(Stuff::LEVELS), 'gte:min_level'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_featured' => ['nullable'],
            'published' => ['nullable'],
        ]);

        $classSlug = array_search($validated['class'], Stuff::CLASSES, true);

        return [
            'title' => $validated['title'],
            'class_slug' => $classSlug ?: Stuff::classSlug($validated['class']),
            'class_label' => $validated['class'],
            'elements' => collect(explode('/', $validated['element']))->map(fn ($item) => trim($item))->filter()->values()->all(),
            'mode' => $validated['mode'],
            'min_level' => $validated['min_level'],
            'max_level' => $validated['max_level'] ?: $validated['min_level'],
            'budget' => null,
            'meta' => $validated['meta'] ?? null,
            'author' => null,
            'description' => $validated['comment'] ?? null,
            'dofusbook_url' => $validated['dofusbook_url'],
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('published'),
        ];
    }
}

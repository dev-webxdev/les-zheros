<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Support\PublicUploadManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-gallery', [
            'images' => GalleryImage::query()->latest()->paginate(12),
            'canDeleteGallery' => auth()->user()?->canDeleteInAdminArea('gallery'),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-gallery-create', [
            'image' => new GalleryImage([
                'is_published' => true,
                'taken_at' => today(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        GalleryImage::create($this->payload($request));

        return redirect()->route('admin.galerie.index')->with('admin_toast', [
            'title' => 'Image ajoutée',
            'text' => 'Elle est prête dans la galerie.',
            'type' => 'success',
        ]);
    }

    public function edit(GalleryImage $image): View
    {
        return view('admin.admin-gallery-create', [
            'image' => $image,
        ]);
    }

    public function update(Request $request, GalleryImage $image): RedirectResponse
    {
        $image->update($this->payload($request, $image));

        return redirect()->route('admin.galerie.index')->with('admin_toast', [
            'title' => 'Image modifiée',
            'text' => 'La galerie a bien été mise à jour.',
            'type' => 'success',
        ]);
    }

    public function destroy(GalleryImage $image): RedirectResponse
    {
        $image->delete();

        return redirect()->route('admin.galerie.index')->with('admin_toast', [
            'title' => 'Image en corbeille',
            'text' => 'Elle a été déplacée dans la corbeille.',
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

        if (in_array($data['action'], ['trash', 'force_delete'], true)) {
            abort_unless($request->user()?->canDeleteInAdminArea('gallery'), 403);
        }

        $images = $data['action'] === 'trash'
            ? GalleryImage::whereKey($data['ids'])->get()
            : GalleryImage::onlyTrashed()->whereKey($data['ids'])->get();

        if ($data['action'] === 'trash') {
            $images->each->delete();
        } elseif ($data['action'] === 'restore') {
            $images->each->restore();
        } else {
            $images->each->forceDelete();
        }

        return back()->with('admin_toast', [
            'title' => 'Action groupée terminée',
            'text' => $images->count().' image(s) traitée(s).',
            'type' => $data['action'] === 'force_delete' ? 'warning' : 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-gallery-trash', [
            'images' => GalleryImage::onlyTrashed()->latest('deleted_at')->paginate(12),
        ]);
    }

    public function restore(int $image): RedirectResponse
    {
        GalleryImage::onlyTrashed()->findOrFail($image)->restore();

        return redirect()->route('admin.galerie.trash')->with('admin_toast', [
            'title' => 'Image restaurée',
            'text' => 'Elle est de retour dans la galerie.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $image): RedirectResponse
    {
        GalleryImage::onlyTrashed()->findOrFail($image)->forceDelete();

        return redirect()->route('admin.galerie.trash')->with('admin_toast', [
            'title' => 'Image supprimée',
            'text' => 'Elle a été supprimée définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        GalleryImage::onlyTrashed()->forceDelete();

        return redirect()->route('admin.galerie.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Toutes les images supprimées ont été effacées.',
            'type' => 'warning',
        ]);
    }

    private function payload(Request $request, ?GalleryImage $image = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => [$image?->exists ? 'nullable' : 'required_without:image_url', 'image', 'max:6144'],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'taken_at' => ['nullable', 'date'],
            'published' => ['nullable'],
        ]);

        $imagePath = $image?->image_path;

        if ($request->hasFile('image')) {
            $imagePath = PublicUploadManager::store($request->file('image'), 'gallery', 'gallery');
        } elseif (! empty($validated['image_url'])) {
            $imagePath = $validated['image_url'];
        }

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image_path' => $imagePath,
            'is_published' => $request->boolean('published'),
            'taken_at' => $validated['taken_at'] ?? null,
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-announcements', [
            'announcements' => Announcement::with('user')->latest()->paginate(12),
            'canDeleteAnnouncements' => auth()->user()?->canDeleteInAdminArea('announcements'),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-announcement-create', [
            'announcement' => new Announcement([
                'status' => 'published',
                'tag' => 'info',
                'published_at' => now(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Announcement::create($this->payload($request));

        return redirect()->route('admin.annonces.index')->with('admin_toast', [
            'title' => 'Annonce créée',
            'text' => 'L’annonce a bien été enregistrée.',
            'type' => 'success',
        ]);
    }

    public function edit(Announcement $announcement): View
    {
        if ($announcement->statusForForm() === 'published') {
            $announcement->status = 'published';
        }

        return view('admin.admin-announcement-create', [
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->payload($request));

        return redirect()->route('admin.annonces.index')->with('admin_toast', [
            'title' => 'Annonce modifiée',
            'text' => 'Les modifications ont bien été enregistrées.',
            'type' => 'success',
        ]);
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.annonces.index')->with('admin_toast', [
            'title' => 'Annonce en corbeille',
            'text' => 'L’annonce a été déplacée dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-announcements-trash', [
            'announcements' => Announcement::onlyTrashed()->with('user')->latest('deleted_at')->paginate(12),
        ]);
    }

    public function restore(int $announcement): RedirectResponse
    {
        Announcement::onlyTrashed()->findOrFail($announcement)->restore();

        return redirect()->route('admin.annonces.trash')->with('admin_toast', [
            'title' => 'Annonce restaurée',
            'text' => 'Elle est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $announcement): RedirectResponse
    {
        Announcement::onlyTrashed()->findOrFail($announcement)->forceDelete();

        return redirect()->route('admin.annonces.trash')->with('admin_toast', [
            'title' => 'Annonce supprimée',
            'text' => 'Elle a été supprimée définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        Announcement::onlyTrashed()->forceDelete();

        return redirect()->route('admin.annonces.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Toutes les annonces supprimées ont été effacées.',
            'type' => 'warning',
        ]);
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'tag' => ['required', Rule::in(array_keys(Announcement::TAGS))],
            'published_at' => [$request->input('status') === 'scheduled' ? 'required' : 'nullable', 'date'],
            'content' => ['required', 'string', 'max:20000'],
        ]);

        return [
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'tag' => $validated['tag'],
            'excerpt' => null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'scheduled'
                ? $validated['published_at']
                : ($validated['status'] === 'published' ? ($validated['published_at'] ?? now()) : null),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Support\MediaLibraryData;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use UnitEnum;

class MediaLibrary extends Page
{
    use WithPagination;
    use WithoutUrlPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Mediatheque';

    protected static ?string $title = 'Mediatheque';

    protected static ?string $slug = 'mediatheque';

    protected static ?int $navigationSort = 24;

    protected string $view = 'filament.pages.media-library';

    public string $search = '';

    public string $status = 'all';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminArea('media') ?? false;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function mediaItems(): Collection
    {
        return app(MediaLibraryData::class)->images($this->search, $this->status);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedMediaItems(): LengthAwarePaginator
    {
        $items = $this->mediaItems();
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $items->forPage($page, 12)->values(),
            $items->count(),
            12,
            $page,
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function deleteMedia(string $path): void
    {
        $result = app(MediaLibraryData::class)->deleteUnusedImage($path);

        $notification = Notification::make()
            ->title($result['title'])
            ->body($result['body']);

        match ($result['type']) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            default => $notification->danger(),
        };

        $notification->send();
    }
}

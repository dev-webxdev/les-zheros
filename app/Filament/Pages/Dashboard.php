<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Lottery;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Filament\Resources\Guides\GuideResource;
use App\Filament\Resources\Missions\MissionResource;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAdminAccess() ?? false;
    }

    /**
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    public function shortcuts(): array
    {
        return array_values(array_filter([
            $this->canSee('announcements') ? ['label' => 'Annonce', 'url' => AnnouncementResource::getUrl('create'), 'icon' => 'announcements'] : null,
            $this->canSee('missions') ? ['label' => 'Mission', 'url' => MissionResource::getUrl('create'), 'icon' => 'missions'] : null,
            $this->canSee('guides') ? ['label' => 'Guide', 'url' => GuideResource::getUrl('create'), 'icon' => 'guides'] : null,
            $this->canSee('gallery') ? ['label' => 'Image galerie', 'url' => GalleryImageResource::getUrl('create'), 'icon' => 'gallery'] : null,
            $this->canSee('lottery') ? ['label' => 'Loterie', 'url' => Lottery::getUrl(), 'icon' => 'lottery'] : null,
            $this->canSee('users') ? ['label' => 'Utilisateur', 'url' => UserResource::getUrl('create'), 'icon' => 'users'] : null,
        ]));
    }

    private function canSee(string $area): bool
    {
        return auth()->user()?->canAccessAdminArea($area) ?? false;
    }
}

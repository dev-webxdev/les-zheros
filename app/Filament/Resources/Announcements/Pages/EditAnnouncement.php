<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->getRecord()->statusForForm() === 'published') {
            $data['status'] = 'published';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeAnnouncementData($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeAnnouncementData(array $data): array
    {
        return [
            ...$data,
            'user_id' => auth()->id(),
            'excerpt' => null,
            'published_at' => match ($data['status']) {
                'scheduled' => $data['published_at'],
                'published' => $data['published_at'] ?? now(),
                default => null,
            },
        ];
    }
}

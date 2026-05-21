<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeAnnouncementData($data);
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

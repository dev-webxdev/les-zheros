<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Support\PublicUploadManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateGalleryImage extends CreateRecord
{
    protected static string $resource = GalleryImageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeGalleryImageData($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeGalleryImageData(array $data): array
    {
        if (($data['image_upload'] ?? null) instanceof TemporaryUploadedFile) {
            $data['image_path'] = PublicUploadManager::store($data['image_upload'], 'gallery', 'gallery');
        } elseif (filled($data['image_url'] ?? null)) {
            $data['image_path'] = $data['image_url'];
        }

        if (blank($data['image_path'] ?? null)) {
            throw ValidationException::withMessages([
                'data.image_upload' => 'Ajoute une image depuis ton PC ou renseigne une URL.',
            ]);
        }

        unset($data['image_upload']);
        unset($data['image_url']);

        return $data;
    }
}

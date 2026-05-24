<?php

namespace App\Filament\Resources\GalleryImages\Pages;

use App\Filament\Resources\GalleryImages\GalleryImageResource;
use App\Support\PublicUploadManager;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditGalleryImage extends EditRecord
{
    protected static string $resource = GalleryImageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeGalleryImageData($data);
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
    private function normalizeGalleryImageData(array $data): array
    {
        if (($data['image_upload'] ?? null) instanceof TemporaryUploadedFile) {
            $data['image_path'] = PublicUploadManager::store($data['image_upload'], 'gallery', 'gallery');
        } elseif (filled($data['image_url'] ?? null)) {
            $data['image_path'] = $data['image_url'];
        } else {
            $data['image_path'] = $this->record->image_path;
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

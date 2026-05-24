<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Resources\Missions\MissionResource;
use App\Filament\Resources\Missions\Schemas\MissionForm;
use App\Models\Mission;
use App\Support\PublicUploadManager;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditMission extends EditRecord
{
    protected static string $resource = MissionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['selected_image'] = $data['image_path'] ?? null;
        $data['image_url'] = ($data['image_mode'] ?? null) === 'url' ? ($data['image_path'] ?? null) : null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeMissionData($data);
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
    private function normalizeMissionData(array $data): array
    {
        $category = $data['category'];
        $title = $category === 'anomalie'
            ? Mission::anomalyTitle($data['anomaly_type'] ?? null, $data['anomaly_level'] ?? null)
            : ($data['title'] ?? null);

        if (blank($title)) {
            throw ValidationException::withMessages([
                'data.title' => 'Le titre de la mission est obligatoire.',
            ]);
        }

        $imagePath = $this->getRecord()->image_path;

        [$dofusMonsterId, $selectedImage] = MissionForm::parseDofusValue($data['selected_image'] ?? null);

        if ($category === 'anomalie') {
            $imagePath = null;
        } elseif (($data['image_upload'] ?? null) instanceof TemporaryUploadedFile) {
            $imagePath = PublicUploadManager::store(
                $data['image_upload'],
                'missions',
                'mission',
                name: trim($title.' '.$category),
                cleanNameOnly: true,
            );
        } elseif (($data['image_mode'] ?? null) === 'url' && filled($data['image_url'] ?? null)) {
            $imagePath = $data['image_url'];
        } elseif (in_array($data['image_mode'] ?? null, ['api', 'upload'], true) && filled($selectedImage)) {
            $imagePath = $selectedImage;
        }

        return [
            'title' => $title,
            'category' => $category,
            'anomaly_type' => $category === 'anomalie' ? $data['anomaly_type'] : null,
            'anomaly_level' => $category === 'anomalie' ? $data['anomaly_level'] : null,
            'dream_type' => $category === 'songe' ? $data['dream_type'] : null,
            'dream_floor' => $category === 'songe' ? $data['dream_floor'] : null,
            'image_mode' => $category === 'anomalie' ? null : ($data['image_mode'] ?? null),
            'image_path' => $imagePath,
            'monster_id' => $category === 'anomalie' ? null : ($dofusMonsterId ?: ($data['monster_id'] ?? null)),
        ];
    }
}

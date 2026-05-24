<?php

namespace App\Filament\Resources\Stuffs\Pages;

use App\Filament\Resources\Stuffs\StuffResource;
use App\Models\Stuff;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditStuff extends EditRecord
{
    protected static string $resource = StuffResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizeStuffData($data);
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
    private function normalizeStuffData(array $data): array
    {
        $maxLevel = $data['max_level'] ?: $data['min_level'];

        if ((int) $maxLevel < (int) $data['min_level']) {
            throw ValidationException::withMessages([
                'data.max_level' => 'Le niveau max doit etre superieur ou egal au niveau min.',
            ]);
        }

        $classSlug = array_search($data['class_label'], Stuff::CLASSES, true);

        return [
            ...$data,
            'class_slug' => $classSlug ?: Stuff::classSlug($data['class_label']),
            'elements' => collect($data['elements'] ?? [])->filter()->values()->all(),
            'min_level' => (int) $data['min_level'],
            'max_level' => (int) $maxLevel,
            'budget' => null,
            'author' => null,
        ];
    }
}

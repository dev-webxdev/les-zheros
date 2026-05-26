<?php

namespace App\Filament\Resources\MissionValidations\Tables;

use App\Filament\Resources\MissionValidations\MissionValidationResource;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use App\Support\MissionValidationAdminWorkflow;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MissionValidationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['mission', 'user', 'reviewer']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Joueur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mission.title')
                    ->label('Mission')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('has_teammates')
                    ->label('Aide')
                    ->state(fn (MissionValidation $record): string => filled($record->teammates) ? 'Oui' : 'Non')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Oui' ? 'success' : 'gray'),

                TextColumn::make('characters')
                    ->label('Persos')
                    ->sortable(),

                TextColumn::make('points')
                    ->label('Points')
                    ->state(fn (MissionValidation $record): string => number_format($record->estimatedPoints(), 2, ',', ' '))
                    ->sortable(false),

                ImageColumn::make('proof_preview')
                    ->label('Preuve')
                    ->state(fn (MissionValidation $record): ?string => self::proofUrl($record))
                    ->imageWidth(88)
                    ->imageHeight(52)
                    ->checkFileExistence(false)
                    ->extraImgAttributes(['class' => 'lz-validation-proof-thumb'])
                    ->action(self::proofAction('proofPreview')),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (MissionValidation $record): string => $record->statusLabel())
                    ->color(fn (MissionValidation $record): string => match ($record->status) {
                        MissionValidation::VALIDATED => 'success',
                        MissionValidation::REFUSED => 'danger',
                        default => 'info',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(MissionValidation::STATUSES),

                SelectFilter::make('user_id')
                    ->label('Joueur')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),

                SelectFilter::make('mission_id')
                    ->label('Mission')
                    ->options(fn (): array => Mission::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                TrashedFilter::make(),
            ])
            ->recordUrl(fn (MissionValidation $record): string => MissionValidationResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('proof')
                    ->label('Preuve')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->iconButton()
                    ->tooltip('Ouvrir la preuve')
                    ->modalHeading('Preuve de validation')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn (MissionValidation $record) => view('filament.resources.mission-validations.proof-modal', [
                        'record' => $record,
                        'proofUrl' => self::proofUrl($record),
                    ]))
                    ->visible(fn (MissionValidation $record): bool => filled(self::proofUrl($record)) || filled($record->proof_text)),

                Action::make('validate')
                    ->label('Valider')
                    ->icon(Heroicon::OutlinedCheck)
                    ->iconButton()
                    ->color('success')
                    ->visible(fn (MissionValidation $record): bool => $record->status !== MissionValidation::VALIDATED && ! $record->trashed())
                    ->action(fn (MissionValidation $record): bool => self::setStatus($record, MissionValidation::VALIDATED)),

                Action::make('refuse')
                    ->label('Refuser')
                    ->icon(Heroicon::OutlinedXMark)
                    ->iconButton()
                    ->color('danger')
                    ->visible(fn (MissionValidation $record): bool => $record->status !== MissionValidation::REFUSED && ! $record->trashed())
                    ->action(fn (MissionValidation $record): bool => self::setStatus($record, MissionValidation::REFUSED)),

                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->iconButton()
                    ->tooltip('Modifier'),

                DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash)
                    ->iconButton()
                    ->tooltip('Supprimer')
                    ->after(fn (MissionValidation $record): null => self::logTrashed($record)),

                RestoreAction::make()
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->iconButton()
                    ->tooltip('Restaurer')
                    ->after(fn (MissionValidation $record): null => self::logRestored($record)),

                ForceDeleteAction::make()
                    ->icon(Heroicon::OutlinedXMark)
                    ->iconButton()
                    ->tooltip('Supprimer definitivement')
                    ->before(fn (MissionValidation $record): null => self::logForceDeleted($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('validated')
                        ->label('Valider')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('success')
                        ->action(fn (Collection $records): null => self::bulkStatus($records, MissionValidation::VALIDATED)),
                    BulkAction::make('refused')
                        ->label('Refuser')
                        ->icon(Heroicon::OutlinedXMark)
                        ->color('danger')
                        ->action(fn (Collection $records): null => self::bulkStatus($records, MissionValidation::REFUSED)),
                    BulkAction::make('pending')
                        ->label('Remettre en attente')
                        ->icon(Heroicon::OutlinedClock)
                        ->color('info')
                        ->action(fn (Collection $records): null => self::bulkStatus($records, MissionValidation::PENDING)),
                    DeleteBulkAction::make()
                        ->after(fn (Collection $records): null => self::bulkLogTrashed($records)),
                    ForceDeleteBulkAction::make()
                        ->before(fn (Collection $records): null => self::bulkLogForceDeleted($records)),
                    RestoreBulkAction::make()
                        ->after(fn (Collection $records): null => self::bulkLogRestored($records)),
                ]),
            ]);
    }

    private static function setStatus(MissionValidation $record, string $status): bool
    {
        return MissionValidationAdminWorkflow::setStatus($record, $status, auth()->user());
    }

    private static function bulkStatus(Collection $records, string $status): null
    {
        $records->each(fn (MissionValidation $record): bool => self::setStatus($record, $status));

        return null;
    }

    private static function logTrashed(MissionValidation $record): null
    {
        MissionValidationAdminWorkflow::logTrashed($record);

        return null;
    }

    private static function bulkLogTrashed(Collection $records): null
    {
        $records->each(fn (MissionValidation $record): null => self::logTrashed($record));

        return null;
    }

    private static function bulkLogRestored(Collection $records): null
    {
        $records->each(fn (MissionValidation $record): null => self::logRestored($record));

        return null;
    }

    private static function bulkLogForceDeleted(Collection $records): null
    {
        $records->each(fn (MissionValidation $record): null => self::logForceDeleted($record));

        return null;
    }

    private static function logRestored(MissionValidation $record): null
    {
        MissionValidationAdminWorkflow::logRestored($record);

        return null;
    }

    private static function logForceDeleted(MissionValidation $record): null
    {
        MissionValidationAdminWorkflow::logForceDeleted($record);

        return null;
    }

    private static function proofAction(string $name): Action
    {
        return Action::make($name)
            ->modalHeading('Preuve de validation')
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(fn (MissionValidation $record) => view('filament.resources.mission-validations.proof-modal', [
                'record' => $record,
                'proofUrl' => self::proofUrl($record),
            ]))
            ->visible(fn (MissionValidation $record): bool => filled(self::proofUrl($record)) || filled($record->proof_text));
    }

    private static function proofUrl(MissionValidation $record): ?string
    {
        if (filled($record->proof_path)) {
            return $record->proof_path;
        }

        if (filter_var($record->proof_text, FILTER_VALIDATE_URL)) {
            return $record->proof_text;
        }

        return null;
    }
}

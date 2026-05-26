<?php

namespace App\Filament\Resources\Outings\Tables;

use App\Filament\Resources\Outings\OutingResource;
use App\Models\Outing;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class OutingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('votes.user')->withCount('votes'))
            ->columns([
                TextColumn::make('title')
                    ->label('Sortie')
                    ->description(fn (Outing $record): ?string => $record->description)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slot_count')
                    ->label('Creneaux')
                    ->state(fn (Outing $record): int => $record->slotCount()),

                TextColumn::make('votes_count')
                    ->label('Inscrits')
                    ->state(fn (Outing $record): string => $record->votes_count.' / '.$record->places)
                    ->sortable(),

                TextColumn::make('close_at')
                    ->label('Cloture')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Non definie')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->state(fn (Outing $record): string => self::status($record))
                    ->badge()
                    ->color(fn (Outing $record): string => self::statusColor($record)),

                TextColumn::make('confirmed_slot_id')
                    ->label('Creneau valide')
                    ->state(fn (Outing $record): string => $record->confirmed_slot_id ? $record->slotLabel($record->confirmed_slot_id) : 'Aucun')
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label('Publie')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Publie'),

                Filter::make('open')
                    ->label('Ouvertes')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('is_published', true)
                        ->whereNull('confirmed_slot_id')
                        ->where(fn (Builder $query): Builder => $query
                            ->whereNull('close_at')
                            ->orWhere('close_at', '>', now()))),

                Filter::make('closed')
                    ->label('Fermees')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('confirmed_slot_id')
                        ->whereNotNull('close_at')
                        ->where('close_at', '<=', now())),

                Filter::make('confirmed')
                    ->label('Confirmees')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('confirmed_slot_id')),

                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Outing $record): string => OutingResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('confirmSlot')
                    ->label('Confirmer un creneau')
                    ->icon(Heroicon::OutlinedCheck)
                    ->iconButton()
                    ->color('success')
                    ->modalHeading('Confirmer un creneau')
                    ->form([
                        Select::make('slot_id')
                            ->label('Creneau')
                            ->options(fn (Outing $record): array => self::confirmableSlots($record))
                            ->required()
                            ->searchable(),
                    ])
                    ->visible(fn (Outing $record): bool => ! $record->trashed())
                    ->action(fn (Outing $record, array $data): bool => self::confirm($record, (string) ($data['slot_id'] ?? ''))),

                Action::make('closeVotes')
                    ->label('Fermer les votes')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->iconButton()
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Outing $record): bool => ! $record->trashed() && ! $record->confirmed_slot_id && ! $record->isClosed())
                    ->action(fn (Outing $record): bool => $record->forceFill(['close_at' => now()])->save()),

                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->iconButton()
                    ->tooltip('Modifier'),

                DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash)
                    ->iconButton()
                    ->tooltip('Supprimer'),

                RestoreAction::make()
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->iconButton()
                    ->tooltip('Restaurer'),

                ForceDeleteAction::make()
                    ->icon(Heroicon::OutlinedXMark)
                    ->iconButton()
                    ->tooltip('Supprimer definitivement'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    private static function confirm(Outing $record, string $slotId): bool
    {
        $record->loadMissing('votes.user');

        if (! $record->hasSlot($slotId)) {
            throw ValidationException::withMessages([
                'mountedActionsData.0.slot_id' => 'Choisis un creneau existant.',
            ]);
        }

        if ($record->votes->where('slot_id', $slotId)->isEmpty()) {
            throw ValidationException::withMessages([
                'mountedActionsData.0.slot_id' => 'Ce creneau ne peut pas etre valide tant que personne ne l a choisi.',
            ]);
        }

        return $record->forceFill([
            'confirmed_slot_id' => $slotId,
            'confirmed_at' => now(),
        ])->save();
    }

    private static function status(Outing $record): string
    {
        if (! $record->is_published) {
            return 'Brouillon';
        }

        if ($record->confirmed_slot_id) {
            return 'Validee';
        }

        return $record->isClosed() ? 'Cloturee' : 'Ouverte';
    }

    private static function statusColor(Outing $record): string
    {
        if (! $record->is_published) {
            return 'gray';
        }

        if ($record->confirmed_slot_id) {
            return 'success';
        }

        return $record->isClosed() ? 'danger' : 'info';
    }

    /**
     * @return array<string, string>
     */
    private static function confirmableSlots(Outing $record): array
    {
        $record->loadMissing('votes.user');

        $slots = [];

        foreach ($record->schedule ?? [] as $day) {
            foreach ($day['times'] ?? [] as $time) {
                $slotId = $record->slotId((string) $day['date'], (string) $time);
                $votes = $record->votes->where('slot_id', $slotId);

                if ($votes->isEmpty()) {
                    continue;
                }

                $names = $votes
                    ->map(fn ($vote): ?string => $vote->user?->name)
                    ->filter()
                    ->join(', ');

                $slots[$slotId] = $day['date'].' '.$time.' - '.$votes->count().'/'.$record->places.' inscrit(s)'.($names ? ' - '.$names : '');
            }
        }

        return $slots;
    }
}

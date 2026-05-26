<?php

namespace App\Filament\Resources\AdminActivities\Tables;

use App\Filament\Resources\AdminActivities\AdminActivityResource;
use App\Models\AdminActivityLog;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('user_name')
                    ->label('Admin')
                    ->state(fn (AdminActivityLog $record): string => $record->actorName())
                    ->searchable(['user_name'])
                    ->sortable(),

                TextColumn::make('area')
                    ->label('Module')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Evenement')
                    ->description(fn (AdminActivityLog $record): ?string => $record->description)
                    ->searchable(['title', 'description'])
                    ->wrap(),

                TextColumn::make('subject_label')
                    ->label('Cible')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12, 25, 50])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Admin')
                    ->options(fn (): array => User::query()
                        ->whereIn('id', AdminActivityLog::query()->select('user_id')->whereNotNull('user_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('area')
                    ->label('Module')
                    ->options(fn (): array => AdminActivityLog::query()
                        ->select('area')
                        ->distinct()
                        ->orderBy('area')
                        ->pluck('area', 'area')
                        ->mapWithKeys(fn (string $area): array => [$area => ucfirst($area)])
                        ->all())
                    ->searchable(),

                SelectFilter::make('action')
                    ->label('Action')
                    ->options(fn (): array => AdminActivityLog::query()
                        ->select('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all())
                    ->searchable(),

                Filter::make('created_at')
                    ->label('Date')
                    ->form([
                        DatePicker::make('from')
                            ->label('Depuis'),
                        DatePicker::make('until')
                            ->label('Jusqu au'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordUrl(fn (AdminActivityLog $record): string => AdminActivityResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye)
                    ->iconButton()
                    ->tooltip('Voir'),
            ]);
    }
}

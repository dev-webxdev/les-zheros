<?php

namespace App\Filament\Pages;

use App\Support\RankingBoard;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

class Ranking extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Activites';

    protected static ?string $navigationLabel = 'Classement';

    protected static ?string $title = 'Classement';

    protected static ?string $slug = 'classement';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.ranking';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminArea('ranking') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?string $search): Collection => $this->rankingRows($sortColumn, $sortDirection, $search))
            ->columns([
                TextColumn::make('rank')
                    ->label('Rang')
                    ->formatStateUsing(fn (int $state): string => '#'.$state)
                    ->badge()
                    ->color(fn (array $record): string => match ($record['rank']) {
                        1 => 'warning',
                        2 => 'info',
                        3 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('name')
                    ->label('Joueur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('missions')
                    ->label('Missions')
                    ->sortable(),

                TextColumn::make('helps')
                    ->label('Aides')
                    ->sortable(),

                TextColumn::make('week')
                    ->label('Points semaine')
                    ->formatStateUsing(fn (float|int $state): string => $this->formatPoints($state))
                    ->sortable(),

                TextColumn::make('month')
                    ->label('Points mois')
                    ->formatStateUsing(fn (float|int $state): string => $this->formatPoints($state))
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Points totaux')
                    ->formatStateUsing(fn (float|int $state): string => $this->formatPoints($state))
                    ->weight('bold')
                    ->sortable(),
            ])
            ->searchable()
            ->defaultSort('week', 'desc')
            ->paginated(false)
            ->emptyStateHeading('Aucun classement')
            ->emptyStateDescription('Les points apparaitront ici apres validation des missions.');
    }

    public function sortTable(?string $column = null, ?string $direction = null): void
    {
        if (! in_array($column, ['name', 'missions', 'helps', 'week', 'month', 'total'], true)) {
            return;
        }

        $currentColumn = $this->getTableSortColumn();
        $currentDirection = $this->getTableSortDirection();

        if ($currentColumn === null && in_array($column, ['week', 'month', 'total'], true)) {
            $this->tableSort = "{$column}:asc";

            $this->updatedTableSort();

            return;
        }

        $currentColumn ??= 'week';
        $currentDirection ??= 'desc';
        $nextDirection = ($currentColumn === $column && $currentDirection === 'desc')
            ? 'asc'
            : 'desc';

        $this->tableSort = "{$column}:{$nextDirection}";

        $this->updatedTableSort();
    }

    private function rankingRows(?string $sortColumn, ?string $sortDirection, ?string $search): Collection
    {
        $rows = app(RankingBoard::class)->rows()
            ->mapWithKeys(fn (array $row): array => [$row['rank'] => $row]);
        $search = str((string) $search)->lower()->trim()->toString();

        if ($search !== '') {
            $rows = $rows->filter(fn (array $row): bool => str((string) $row['name'])->lower()->contains($search));
        }

        $sortColumn = in_array($sortColumn, ['name', 'week', 'month', 'total', 'missions', 'helps'], true)
            ? $sortColumn
            : 'month';

        $rows = $sortDirection === 'asc'
            ? $rows->sortBy($sortColumn)
            : $rows->sortByDesc($sortColumn);

        return $rows
            ->values()
            ->map(fn (array $row, int $index): array => [
                ...$row,
                'rank' => $index + 1,
            ]);
    }

    public function formatPoints(float|int $points): string
    {
        return rtrim(rtrim(str_replace('.', ',', number_format((float) $points, 2, '.', '')), '0'), ',');
    }
}

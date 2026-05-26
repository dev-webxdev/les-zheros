<x-filament-panels::page>
    @php
        $allItems = $this->mediaItems();
        $items = $this->paginatedMediaItems();
        $totalSize = $allItems->sum('size');
        $unusedCount = $allItems->where('deletable', true)->count();

        $humanSize = function (int|float $bytes): string {
            $units = ['o', 'Ko', 'Mo', 'Go'];
            $size = (float) $bytes;
            $unit = 0;

            while ($size >= 1024 && $unit < count($units) - 1) {
                $size /= 1024;
                $unit++;
            }

            return number_format($size, $unit === 0 ? 0 : 1, ',', ' ').' '.$units[$unit];
        };
    @endphp

    <section class="lz-media-page">
        <div class="lz-page-title">
            <div>
                <span class="lz-page-kicker">Administration</span>
                <h1>Mediatheque</h1>
                <p>{{ $allItems->count() }} image(s), {{ $humanSize($totalSize) }}, {{ $unusedCount }} supprimable(s).</p>
            </div>
        </div>

        <div class="lz-media-toolbar">
            <label class="lz-media-search">
                <x-heroicon-o-magnifying-glass />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un fichier...">
            </label>

            <select wire:model.live="status">
                <option value="all">Tous les statuts</option>
                <option value="used">Utilisees</option>
                <option value="unused">Inutilisees</option>
            </select>
        </div>

        <div class="lz-media-table-card">
            <table class="lz-media-table">
                <thead>
                    <tr>
                        <th>Apercu</th>
                        <th>Fichier</th>
                        <th>Type</th>
                        <th>Poids</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr wire:key="media-{{ md5($item['path']) }}">
                            <td>
                                <a class="lz-media-thumb" href="{{ $item['url'] }}" target="_blank" rel="noopener">
                                    <img src="{{ $item['url'] }}" alt="">
                                </a>
                            </td>
                            <td>
                                <strong>{{ $item['name'] }}</strong>
                                <span>{{ $item['path'] }}</span>
                            </td>
                            <td><span class="lz-media-chip">{{ $item['type'] }}</span></td>
                            <td>{{ $item['size_human'] }}</td>
                            <td>{{ $item['modified_at'] }}</td>
                            <td>
                                @if($item['deletable'])
                                    <span class="lz-media-status lz-media-status--unused">Inutilisee</span>
                                @elseif($item['used'])
                                    <span class="lz-media-status lz-media-status--used">Utilisee</span>
                                @else
                                    <span class="lz-media-status">Verrouillee</span>
                                @endif
                            </td>
                            <td>
                                <div class="lz-media-actions">
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" title="Ouvrir">
                                        <x-heroicon-o-arrow-top-right-on-square />
                                    </a>

                                    @if($item['deletable'])
                                        <button
                                            type="button"
                                            class="is-danger"
                                            title="Supprimer"
                                            wire:click="deleteMedia('{{ $item['path'] }}')"
                                            wire:confirm="Supprimer cette image ? Le fichier sera supprime definitivement."
                                        >
                                            <x-heroicon-o-trash />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="lz-media-empty">
                                    <x-heroicon-o-photo />
                                    <strong>Aucune image</strong>
                                    <span>Aucun fichier uploade ne correspond aux filtres.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="lz-media-pagination">
                {{ $items->links() }}
            </div>
        @endif
    </section>
</x-filament-panels::page>

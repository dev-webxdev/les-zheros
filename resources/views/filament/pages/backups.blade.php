<x-filament-panels::page>
    @php
        $backups = $this->backups();

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

        $formatDate = fn (mixed $timestamp): string => is_numeric($timestamp)
            ? date('d/m/Y H:i', (int) $timestamp)
            : (string) $timestamp;
    @endphp

    <section class="lz-backups-page">
        <div class="lz-dashboard-simple-head">
            <div>
                <h1>Sauvegardes</h1>
                <p>{{ $backups->count() }} archive(s) disponible(s). Creation, telechargement, restauration et suppression.</p>
            </div>

            <button
                type="button"
                class="lz-backup-primary-button"
                wire:click="createBackup"
                wire:confirm="Creer une sauvegarde maintenant ? Cela peut prendre quelques secondes."
                wire:loading.attr="disabled"
            >
                <x-heroicon-o-archive-box-arrow-down />
                <span>Creer une sauvegarde</span>
            </button>
        </div>

        <div class="lz-backup-warning">
            <x-heroicon-o-exclamation-triangle />
            <div>
                <strong>Restauration complete avec double confirmation</strong>
                <p>Cette action remplace la base de donnees, les uploads et les images publiques. Une sauvegarde de securite est creee automatiquement avant la restauration.</p>
                <a href="{{ $this->oldSettingsUrl() }}">Ancien module toujours disponible en secours</a>
            </div>
        </div>

        <div class="lz-media-table-card">
            <table class="lz-media-table lz-backups-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Taille</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr wire:key="backup-{{ md5($backup['name']) }}">
                            <td>
                                <strong>{{ $backup['display_name'] ?? $backup['name'] }}</strong>
                                <span>{{ $backup['name'] }}</span>
                            </td>
                            <td><span class="lz-media-chip">{{ $backup['type_label'] ?? 'Sauvegarde' }}</span></td>
                            <td>{{ $humanSize((int) ($backup['size'] ?? 0)) }}</td>
                            <td>{{ $formatDate($backup['created_at'] ?? null) }}</td>
                            <td>
                                <div class="lz-media-actions lz-backup-actions">
                                    <a href="{{ $this->downloadUrl($backup['name']) }}" title="Telecharger">
                                        <x-heroicon-o-arrow-down-tray />
                                    </a>

                                    <div class="lz-backup-restore-action" x-data="{ confirmation: '' }">
                                        <input
                                            type="text"
                                            x-model="confirmation"
                                            placeholder="RESTAURER"
                                            aria-label="Confirmation restauration {{ $backup['name'] }}"
                                        />

                                        <button
                                            type="button"
                                            class="is-restore"
                                            title="Restaurer"
                                            x-bind:disabled="confirmation !== 'RESTAURER'"
                                            wire:loading.attr="disabled"
                                            x-on:click="
                                                if (confirmation !== 'RESTAURER') {
                                                    return;
                                                }

                                                if (! confirm('Derniere confirmation : restaurer cette sauvegarde ? Cette action remplace la base de donnees, les uploads et les images publiques.')) {
                                                    return;
                                                }

                                                $wire.restoreBackup(@js($backup['name']), confirmation);
                                            "
                                        >
                                            <x-heroicon-o-arrow-path />
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        class="is-danger"
                                        title="Supprimer"
                                        wire:click="deleteBackup(@js($backup['name']))"
                                        wire:confirm="Supprimer definitivement cette sauvegarde ? Cette archive ne pourra pas etre recuperee."
                                        wire:loading.attr="disabled"
                                    >
                                        <x-heroicon-o-trash />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="lz-media-empty">
                                    <x-heroicon-o-archive-box />
                                    <strong>Aucune sauvegarde</strong>
                                    <span>Aucune archive n'a encore ete creee.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>

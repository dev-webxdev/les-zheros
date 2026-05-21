@if ($missions->isEmpty())
    <div class="admin-empty-state admin-empty-state--panel">
        <i class="fa-regular fa-trash-can"></i>
        <strong>Corbeille vide</strong>
        <span>Aucune mission n'est actuellement dans la corbeille.</span>
    </div>
@else
    <div class="admin-table-card">
        <table class="admin-table admin-table--missions admin-table--actions-center">
            <thead>
                <tr>
                    <th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="missions-trash-bulk-form" aria-label="Tout sélectionner"></th>
                    <th>Image</th>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Suppression</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($missions as $mission)
                    <tr>
                        <td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $mission->id }}" form="missions-trash-bulk-form" data-bulk-item aria-label="Sélectionner {{ $mission->title }}"></td>
                        <td><img class="admin-mission-thumb" src="{{ $mission->imageUrl() }}" alt="{{ $mission->title }}"></td>
                        <td>{{ $mission->title }}</td>
                        <td>
                            <span class="admin-tag">{{ $mission->categoryLabel() }}</span>
                            @if ($mission->category === 'songe')
                                <span class="admin-tag">{{ $mission->dreamTypeLabel() }}</span>
                                <span class="admin-tag">Palier {{ $mission->dream_floor }}</span>
                            @endif
                        </td>
                        <td>{{ $mission->deleted_at?->translatedFormat('d M Y') }}</td>
                        <td>
                            <div class="admin-row-actions">
                                <form action="{{ route('admin.missions.restore', $mission->id) }}" method="post" data-real-form>
                                    @csrf
                                    @method('patch')
                                    <button class="admin-action-button admin-action-button--restore" type="submit" aria-label="Restaurer {{ $mission->title }}" title="Restaurer">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </form>
                                @if ($canDeleteMissions)
                                    <form action="{{ route('admin.missions.force-delete', $mission->id) }}" method="post" data-real-form>
                                        @csrf
                                        @method('delete')
                                        <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer definitivement {{ $mission->title }}" title="Supprimer definitivement">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="admin-table-card">
    <table class="admin-table admin-table--outings-trash admin-table--actions-center">
        <thead>
            <tr>
                <th>Sortie</th>
                <th>Statut</th>
                <th>Supprimee</th>
                <th>Creneaux</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($outings as $outing)
                <tr>
                    <td><div class="admin-announcement-cell"><strong>{{ $outing->title }}</strong><span>{{ $outing->description ?: 'Aucune description.' }}</span></div></td>
                    <td><span class="admin-tag">Archivee</span></td>
                    <td>{{ $outing->deleted_at?->translatedFormat('d M Y') }}</td>
                    <td>{{ $outing->slotCount() }}</td>
                    <td>
                        <div class="admin-row-actions">
                            <form action="{{ route('admin.sorties.restore', $outing->id) }}" method="post" data-real-form>
                                @csrf
                                @method('patch')
                                <button class="admin-action-button admin-action-button--restore" type="submit" aria-label="Restaurer {{ $outing->title }}" title="Restaurer">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </form>
                            @if($canDeleteOutings)
                                <form action="{{ route('admin.sorties.force-delete', $outing->id) }}" method="post" data-real-form>
                                    @csrf
                                    @method('delete')
                                    <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer definitivement {{ $outing->title }}" title="Supprimer definitivement">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="admin-empty-state">
                            <strong>Corbeille vide</strong>
                            <span>Aucune sortie supprimee pour le moment.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

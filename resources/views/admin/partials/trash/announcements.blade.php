<div class="admin-table-card">
    <table class="admin-table admin-table--announcements admin-table--announcements-trash admin-table--actions-center">
        <thead><tr><th>Titre</th><th>Statut</th><th>Publication</th><th>Auteur</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($announcements as $announcement)
                <tr>
                    <td><div class="admin-announcement-cell"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->preview() }}</span></div></td>
                    <td><span @class(['admin-tag', $announcement->statusTagClass()])>{{ $announcement->statusLabel() }}</span></td>
                    <td>{{ $announcement->published_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                    <td>{{ $announcement->user?->name ?? 'Admin' }}</td>
                    <td><div class="admin-row-actions">
                        <form action="{{ route('admin.annonces.restore', $announcement->id) }}" method="post" data-real-form>@csrf @method('patch')<button class="admin-action-button admin-action-button--restore" type="submit" title="Restaurer"><i class="fa-solid fa-rotate-left"></i></button></form>
                        <form action="{{ route('admin.annonces.force-delete', $announcement->id) }}" method="post" data-real-form>@csrf @method('delete')<button class="admin-action-button admin-action-button--delete" type="submit" title="Supprimer definitivement"><i class="fa-regular fa-trash-can"></i></button></form>
                    </div></td>
                </tr>
            @empty
                <tr class="admin-table-empty-row"><td colspan="5"><div class="admin-empty-state"><i class="fa-regular fa-trash-can"></i><strong>Corbeille vide</strong><span>Aucune annonce supprimee.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

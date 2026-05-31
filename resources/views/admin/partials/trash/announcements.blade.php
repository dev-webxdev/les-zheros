@component('admin.components.table-card')
    @component('admin.components.table', ['class' => 'admin-table--announcements admin-table--announcements-trash admin-table--actions-center'])
        <thead><tr><th>Titre</th><th>Statut</th><th>Publication</th><th>Auteur</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($announcements as $announcement)
                <tr>
                    <td><div class="admin-announcement-cell"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->preview() }}</span></div></td>
                    <td>@component('admin.components.badge', ['class' => $announcement->statusTagClass(), 'label' => $announcement->statusLabel()])@endcomponent</td>
                    <td>{{ $announcement->published_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                    <td>{{ $announcement->user?->name ?? 'Admin' }}</td>
                    <td>
                        @component('admin.components.trash-actions', [
                            'restoreUrl' => route('admin.annonces.restore', $announcement->id),
                            'deleteUrl' => route('admin.annonces.force-delete', $announcement->id),
                            'canDelete' => $canForceDeleteAnnouncements,
                        ])@endcomponent
                    </td>
                </tr>
            @empty
                @component('admin.components.table-empty-row', ['colspan' => 5])
                    @component('admin.components.empty-state', ['icon' => 'fa-regular fa-trash-can', 'title' => 'Corbeille vide', 'text' => 'Aucune annonce supprimee.'])@endcomponent
                @endcomponent
            @endforelse
        </tbody>
    @endcomponent
@endcomponent

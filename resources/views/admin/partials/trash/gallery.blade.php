@component('admin.components.table-card')
    @component('admin.components.table', ['class' => 'admin-table--gallery-trash'])
        <thead>
            <tr>
                <th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="gallery-trash-bulk-form" aria-label="Tout sélectionner"></th>
                <th>Apercu</th>
                <th>Image</th>
                <th>Supprimee</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($images as $image)
                <tr>
                    <td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $image->id }}" form="gallery-trash-bulk-form" data-bulk-item aria-label="Sélectionner {{ $image->title }}"></td>
                    <td><img class="admin-gallery-thumb" src="{{ $image->imageUrl() }}" alt="{{ $image->title }}"></td>
                    <td><div class="admin-announcement-cell"><strong>{{ $image->title }}</strong><span>{{ $image->description ?: 'Aucune description ajoutee.' }}</span></div></td>
                    <td>{{ $image->deleted_at?->translatedFormat('d M Y') }}</td>
                    <td>
                        @component('admin.components.trash-actions', [
                            'restoreUrl' => route('admin.galerie.restore', $image->id),
                            'deleteUrl' => route('admin.galerie.force-delete', $image->id),
                            'canDelete' => $canForceDeleteGallery,
                            'restoreAria' => 'Restaurer '.$image->title,
                            'deleteAria' => 'Supprimer definitivement '.$image->title,
                        ])@endcomponent
                    </td>
                </tr>
            @empty
                @component('admin.components.table-empty-row', ['colspan' => 5])
                    @component('admin.components.empty-state', ['title' => 'Aucune image supprimee', 'text' => 'La corbeille est vide.'])@endcomponent
                @endcomponent
            @endforelse
        </tbody>
    @endcomponent
@endcomponent

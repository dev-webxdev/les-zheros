<div class="admin-table-card">
    <table class="admin-table admin-table--gallery-trash">
        <thead>
            <tr>
                <th>Apercu</th>
                <th>Image</th>
                <th>Supprimee</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($images as $image)
                <tr>
                    <td><img class="admin-gallery-thumb" src="{{ $image->imageUrl() }}" alt="{{ $image->title }}"></td>
                    <td><div class="admin-announcement-cell"><strong>{{ $image->title }}</strong><span>{{ $image->description ?: 'Aucune description ajoutee.' }}</span></div></td>
                    <td>{{ $image->deleted_at?->translatedFormat('d M Y') }}</td>
                    <td>
                        <div class="admin-row-actions">
                            <form action="{{ route('admin.galerie.restore', $image->id) }}" method="post" data-real-form>
                                @csrf
                                @method('patch')
                                <button class="admin-action-button admin-action-button--restore" type="submit" aria-label="Restaurer {{ $image->title }}" title="Restaurer"><i class="fa-solid fa-rotate-left"></i></button>
                            </form>
                            <form action="{{ route('admin.galerie.force-delete', $image->id) }}" method="post" data-real-form>
                                @csrf
                                @method('delete')
                                <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer definitivement {{ $image->title }}" title="Supprimer definitivement"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="admin-table-empty-row"><td colspan="4"><div class="admin-empty-state"><strong>Aucune image supprimee</strong><span>La corbeille est vide.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

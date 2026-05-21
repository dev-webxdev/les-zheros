<div class="admin-table-card">
    <table class="admin-table admin-table--stuffs">
        <thead>
            <tr>
                <th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="stuffs-trash-bulk-form" aria-label="Tout sélectionner"></th>
                <th>Classe</th>
                <th>Build</th>
                <th>Elements</th>
                <th>Mode</th>
                <th>Niveau</th>
                <th>Supprime</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stuffs as $stuff)
                <tr>
                    <td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $stuff->id }}" form="stuffs-trash-bulk-form" data-bulk-item aria-label="Sélectionner {{ $stuff->title }}"></td>
                    <td><span class="admin-tag">{{ $stuff->class_label }}</span></td>
                    <td><strong>{{ $stuff->title }}</strong></td>
                    <td>{{ implode(' / ', $stuff->elements ?? []) }}</td>
                    <td><span class="admin-tag admin-tag--primary">{{ $stuff->mode }}</span></td>
                    <td>{{ $stuff->levelLabel() }}</td>
                    <td>{{ $stuff->deleted_at?->translatedFormat('d M Y') }}</td>
                    <td>
                        <div class="admin-row-actions admin-stuff-actions">
                            <form action="{{ route('admin.stuffs.restore', $stuff->id) }}" method="post" data-real-form>
                                @csrf
                                @method('patch')
                                <button class="admin-action-button admin-action-button--restore" type="submit" aria-label="Restaurer {{ $stuff->title }}" title="Restaurer"><i class="fa-solid fa-rotate-left"></i></button>
                            </form>
                            <form action="{{ route('admin.stuffs.force-delete', $stuff->id) }}" method="post" data-real-form>
                                @csrf
                                @method('delete')
                                <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer definitivement {{ $stuff->title }}" title="Supprimer definitivement"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="admin-table-empty-row">
                    <td colspan="8">
                        <div class="admin-empty-state">
                            <i class="fa-regular fa-trash-can"></i>
                            <strong>Corbeille vide</strong>
                            <span>Aucun stuff supprime pour le moment.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@component('admin.components.table-card')
    @component('admin.components.table', ['class' => 'admin-table--stuffs'])
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
                    <td>@component('admin.components.badge', ['label' => $stuff->class_label])@endcomponent</td>
                    <td><strong>{{ $stuff->title }}</strong></td>
                    <td>{{ implode(' / ', $stuff->elements ?? []) }}</td>
                    <td>@component('admin.components.badge', ['class' => 'admin-tag--primary', 'label' => $stuff->mode])@endcomponent</td>
                    <td>{{ $stuff->levelLabel() }}</td>
                    <td>{{ $stuff->deleted_at?->translatedFormat('d M Y') }}</td>
                    <td>
                        @component('admin.components.trash-actions', [
                            'class' => 'admin-stuff-actions',
                            'restoreUrl' => route('admin.stuffs.restore', $stuff->id),
                            'deleteUrl' => route('admin.stuffs.force-delete', $stuff->id),
                            'canDelete' => $canForceDeleteStuffs,
                            'restoreAria' => 'Restaurer '.$stuff->title,
                            'deleteAria' => 'Supprimer definitivement '.$stuff->title,
                        ])@endcomponent
                    </td>
                </tr>
            @empty
                @component('admin.components.table-empty-row', ['colspan' => 8])
                    @component('admin.components.empty-state', ['icon' => 'fa-regular fa-trash-can', 'title' => 'Corbeille vide', 'text' => 'Aucun stuff supprime pour le moment.'])@endcomponent
                @endcomponent
            @endforelse
        </tbody>
    @endcomponent
@endcomponent

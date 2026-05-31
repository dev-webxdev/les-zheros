@if ($validations->isEmpty())
    <div class="admin-empty-state admin-empty-state--panel">
        <i class="fa-regular fa-trash-can"></i>
        <strong>Corbeille vide</strong>
        <span>Aucune validation n'est actuellement dans la corbeille.</span>
    </div>
@else
    <div class="admin-table-card admin-validation-table-card">
        <table class="admin-table admin-table--validations">
            <thead>
                <tr>
                    <th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="validations-trash-bulk-form" aria-label="Tout sélectionner"></th>
                    <th>Joueur</th>
                    <th>Mission</th>
                    <th>Persos</th>
                    <th>Statut</th>
                    <th>Suppression</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($validations as $validation)
                    <tr>
                        <td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $validation->id }}" form="validations-trash-bulk-form" data-bulk-item aria-label="Sélectionner la validation de {{ $validation->user?->name }}"></td>
                        <td>
                            <div class="admin-user-cell">
                                <span class="admin-user-avatar">
                                    @if ($validation->user?->avatarUrl())
                                        <img src="{{ $validation->user->avatarUrl() }}" alt="Photo de {{ $validation->user->name }}">
                                    @else
                                        {{ $validation->user?->initials() ?? 'US' }}
                                    @endif
                                </span>
                                <strong>{{ $validation->user?->name ?? 'Utilisateur supprime' }}</strong>
                            </div>
                        </td>
                        <td><strong>{{ $validation->mission?->title ?? 'Mission supprimee' }}</strong></td>
                        <td>{{ $validation->characters }}</td>
                        <td><span @class(['admin-tag', $validation->statusTagClass()])>{{ $validation->statusLabel() }}</span></td>
                        <td>{{ $validation->deleted_at?->translatedFormat('d M Y') }}</td>
                        <td>
                            <div class="admin-row-actions">
                                <form action="{{ route('admin.validations.restore', $validation->id) }}" method="post" data-real-form>
                                    @csrf
                                    @method('patch')
                                    <button class="admin-action-button admin-action-button--restore" type="submit" title="Restaurer">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </form>
                                @if ($canForceDeleteValidations)
                                    <form action="{{ route('admin.validations.force-delete', $validation->id) }}" method="post" data-real-form>
                                        @csrf
                                        @method('delete')
                                        <button class="admin-action-button admin-action-button--delete" type="submit" title="Supprimer definitivement">
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

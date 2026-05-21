@if ($users->isEmpty())
    <div class="admin-empty-state admin-empty-state--panel">
        <i class="fa-regular fa-trash-can"></i>
        <strong>Corbeille vide</strong>
        <span>Aucun utilisateur n'est actuellement dans la corbeille.</span>
    </div>
@else
    <div class="admin-table-card">
        <table class="admin-table admin-table--users admin-table--actions-center">
            <thead>
                <tr>
                    <th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="users-trash-bulk-form" aria-label="Tout sélectionner"></th>
                    <th>Pseudo</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Suppression</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $user->id }}" form="users-trash-bulk-form" data-bulk-item aria-label="Sélectionner {{ $user->name }}"></td>
                        <td>
                            <div class="admin-user-cell">
                                <span class="admin-user-avatar">
                                    @if ($user->avatarUrl())
                                        <img src="{{ $user->avatarUrl() }}" alt="Photo de {{ $user->name }}">
                                    @else
                                        {{ $user->initials() }}
                                    @endif
                                </span>
                                <strong>{{ $user->name }}</strong>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @php($displayRoles = \App\Support\AdminAccess::displayRoles($user->adminRoles()))
                            @foreach ($displayRoles as $role)
                                <span @class(['admin-tag', \App\Support\AdminAccess::roleTagClass($role)])>{{ \App\Support\AdminAccess::roles()[$role] ?? $role }}</span>
                            @endforeach
                        </td>
                        <td>{{ $user->deleted_at?->translatedFormat('d M Y') }}</td>
                        <td>
                            <div class="admin-row-actions">
                                <form action="{{ route('admin.utilisateurs.restore', $user->id) }}" method="post" data-real-form>
                                    @csrf
                                    @method('patch')
                                    <button class="admin-action-button admin-action-button--restore" type="submit" aria-label="Restaurer {{ $user->name }}" title="Restaurer">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </form>
                                @if ($canDeleteUsers)
                                    <form action="{{ route('admin.utilisateurs.force-delete', $user->id) }}" method="post" data-real-form>
                                        @csrf
                                        @method('delete')
                                        <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer definitivement {{ $user->name }}" title="Supprimer definitivement">
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

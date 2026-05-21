<div class="admin-table-card">
    <table class="admin-table admin-table--roles admin-table--actions-center">
        <thead>
            <tr>
                <th>Role</th>
                <th>Permissions</th>
                <th>Suppression</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>
                        <div class="admin-role-cell">
                            <span @class(['admin-tag', \App\Support\AdminAccess::roleTagClass($role->key)])>{{ $role->label }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="admin-tag">
                            {{ count($role->permissions ?? []) === 1 ? '1 permission' : count($role->permissions ?? []).' permissions' }}
                        </span>
                    </td>
                    <td>{{ $role->deleted_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="admin-row-actions">
                            <form action="{{ route('admin.roles.restore', $role->id) }}" method="post" data-real-form>
                                @csrf
                                @method('patch')
                                <button class="admin-action-button admin-action-button--restore" type="submit" aria-label="Restaurer {{ $role->label }}" title="Restaurer">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.roles.force-delete', $role->id) }}" method="post" data-real-form>
                                @csrf
                                @method('delete')
                                <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer definitivement {{ $role->label }}" title="Supprimer definitivement">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="admin-table-empty-row">
                    <td colspan="4">
                        <div class="admin-empty-state">
                            <i class="fa-regular fa-trash-can"></i>
                            <strong>Corbeille vide</strong>
                            <span>Aucun role n'est actuellement dans la corbeille.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

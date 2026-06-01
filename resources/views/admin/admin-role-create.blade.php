@extends('layouts.admin')

@section('title', ($mode === 'edit' ? 'Modifier un rôle' : 'Créer un rôle') . ' | Les Zheros')
@section('description', 'Gestion d\'un rôle de la guilde Les Zheros.')
@php($activeAdmin = 'admin-roles')
@push('scripts')
<script src="{{ asset('assets/js/admin-roles.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Rôles / {{ $mode === 'edit' ? 'Modifier' : 'Créer' }}</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.roles.index') }}">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Retour aux rôles</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>{{ $mode === 'edit' ? 'Modifier un rôle' : 'Créer un rôle' }}</h1>
        </div>

        <section class="admin-form-card" aria-labelledby="role-form-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="role-form-title">Informations du rôle</h2>
                    <p>Définis le nom, la couleur et les permissions associées au rôle.</p>
                </div>
            </div>

            <form class="admin-mission-form" action="{{ $mode === 'edit' ? route('admin.roles.update', $roleKey) : route('admin.roles.store') }}" method="post" data-real-form>
                @csrf
                @if ($mode === 'edit')
                    @method('patch')
                @endif
                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>1</span>
                        <div>
                            <h3>Identité</h3>
                            <p>Le rôle sera assignable aux utilisateurs depuis leur fiche.</p>
                        </div>
                    </div>

                    <div class="admin-form-grid admin-form-grid--role">
                        <label class="admin-field" for="role-name">
                            <span>Nom du rôle</span>
                            <input id="role-name" name="name" type="text" value="{{ $roleLabel }}" placeholder="Ex: Recruteur" required>
                        </label>

                        <div class="admin-role-color-select" data-role-color-picker>
                            <label class="admin-field" for="role-color">
                                <span>Couleur</span>
                                <select id="role-color" name="color" data-role-color-value required>
                                    <option value="primary" @selected($roleColor === 'primary')>Bleu</option>
                                    <option value="success" @selected($roleColor === 'success')>Vert</option>
                                    <option value="danger" @selected($roleColor === 'danger')>Rouge</option>
                                    <option value="warning" @selected($roleColor === 'warning')>Orange</option>
                                    <option value="violet" @selected($roleColor === 'violet')>Violet</option>
                                    <option value="teal" @selected($roleColor === 'teal')>Sarcelle</option>
                                    <option value="pink" @selected($roleColor === 'pink')>Rose</option>
                                    <option value="sky" @selected($roleColor === 'sky')>Ciel</option>
                                    <option value="neutral" @selected($roleColor === 'neutral')>Neutre</option>
                                </select>
                            </label>
                            <div class="admin-role-color-preview" aria-live="polite">
                                <span class="admin-tag admin-tag--{{ $roleColor }}" data-role-color-preview>{{ ucfirst($roleColor) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>2</span>
                        <div>
                            <h3>Permissions</h3>
                            <p>Glisse les droits actifs dans la colonne du rôle.</p>
                        </div>
                    </div>

                    @if ($roleIsLocked ?? false)
                        <div class="admin-role-locked-note">
                            <i class="fa-solid fa-lock"></i>
                            <div>
                                <strong>Accès administrateur verrouillé</strong>
                                <span>Le rôle Administrateur garde les droits de gestion du site, sauf maintenance et sauvegardes qui restent réservées au rôle Développeur web.</span>
                            </div>
                        </div>
                    @else
                    <div class="admin-transfer-board" data-permission-board>
                        <section class="admin-transfer-board__column admin-transfer-board__column--available">
                            <div class="admin-transfer-board__head">
                                <h4>Permissions disponibles</h4>
                                <span>À ajouter</span>
                            </div>
                            <div class="admin-transfer-board__list" data-permission-list="available" aria-label="Permissions disponibles">
                                @foreach ($permissionCategories as $categoryKey => $category)
                                    @php($categoryPermissions = $availablePermissions->only($category['permissions']))
                                    @if ($categoryPermissions->isNotEmpty())
                                        <div class="admin-permission-group" data-permission-category-list="{{ $categoryKey }}">
                                            <div class="admin-permission-group__head">
                                                <i class="{{ $category['icon'] }}"></i>
                                                <span>{{ $category['label'] }}</span>
                                            </div>
                                            <div class="admin-permission-group__items">
                                                @foreach ($categoryPermissions as $permission => $label)
                                                    <button class="admin-transfer-board__chip" type="button" draggable="true" data-permission="{{ $permission }}" data-permission-category="{{ $categoryKey }}">
                                                        <i class="fa-solid fa-grip-vertical"></i>
                                                        <span>{{ $label }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>

                        <section class="admin-transfer-board__column admin-transfer-board__column--selected">
                            <div class="admin-transfer-board__head">
                                <h4>Permissions du rôle</h4>
                                <span>Actifs</span>
                            </div>
                            <div class="admin-transfer-board__list" data-permission-list="selected" aria-label="Permissions du rôle">
                                <p class="admin-transfer-board__empty" data-permission-empty>Glisse les droits ici.</p>
                                @foreach ($permissionCategories as $categoryKey => $category)
                                    @php($categorySelectedPermissions = collect($selectedPermissionKeys)->filter(fn (string $permission): bool => in_array($permission, $category['permissions'], true) && isset($permissions[$permission])))
                                    @if ($categorySelectedPermissions->isNotEmpty())
                                        <div class="admin-permission-group" data-permission-category-list="{{ $categoryKey }}">
                                            <div class="admin-permission-group__head">
                                                <i class="{{ $category['icon'] }}"></i>
                                                <span>{{ $category['label'] }}</span>
                                            </div>
                                            <div class="admin-permission-group__items">
                                                @foreach ($categorySelectedPermissions as $permission)
                                                    <button class="admin-transfer-board__chip" type="button" draggable="true" data-permission="{{ $permission }}" data-permission-category="{{ $categoryKey }}">
                                                        <i class="fa-solid fa-grip-vertical"></i>
                                                        <span>{{ $permissions[$permission] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <div data-permission-inputs></div>
                        </section>
                    </div>
                    @endif
                </section>

                <div class="admin-form-actions">
                    <a class="admin-secondary-button" href="{{ route('admin.roles.index') }}">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Annuler</span>
                    </a>
                    <button class="admin-create-button" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Enregistrer</span>
                    </button>
                </div>
            </form>
        </section>
    </section>
</main>
@endsection

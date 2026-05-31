@extends('layouts.admin')

@section('title', 'Modifier un utilisateur | Les Zheros')
@section('description', 'Modification d\'un utilisateur de la guilde Les Zheros.')
@php($activeAdmin = 'admin-users')
@php($canManageUserRoles = auth()->user()?->hasAdminRole(\App\Support\AdminAccess::ADMIN) ?? false)
@push('scripts')
<script src="{{ asset('assets/js/admin-users.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Utilisateurs / Modifier</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.utilisateurs.index') }}">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Retour aux utilisateurs</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-user-pen"></i>
            <h1>Modifier un utilisateur</h1>
        </div>

        @if ($errors->any())
            <div class="admin-empty-state">
                <strong>Formulaire incomplet</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <section class="admin-form-card" aria-labelledby="edit-user-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="edit-user-title">Informations du compte</h2>
                    <p>Modifie le pseudo, l'email, les rôles et le mot de passe.</p>
                </div>
            </div>

            <form class="admin-mission-form" action="{{ route('admin.utilisateurs.update', $user) }}" method="post" enctype="multipart/form-data" data-real-form>
                @csrf
                @method('patch')
                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>1</span>
                        <div>
                            <h3>Identité</h3>
                            <p>Ces informations sont visibles dans l'administration.</p>
                        </div>
                    </div>

                    <div class="admin-form-grid admin-form-grid--user">
                        @php($selectedRoles = old('roles', $user->adminRoles()))
                        @php($availableRoles = collect($roleOptions)->reject(fn (string $label, string $value) => in_array($value, $selectedRoles, true)))

                        <label class="admin-field" for="u-name">
                            <span>Pseudo</span>
                            <input id="u-name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                        </label>

                        <label class="admin-field" for="u-email">
                            <span>Email</span>
                            <input id="u-email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                        </label>

                        <label class="admin-toggle-field">
                            <input type="checkbox" name="is_approved" value="1" @checked(old('is_approved', $user->is_approved))>
                            <span>Compte validé</span>
                        </label>

                        <div class="admin-user-avatar-editor">
                            <span class="admin-user-avatar admin-user-avatar--large">
                                @if ($user->avatarUrl())
                                    <img src="{{ $user->avatarUrl() }}" alt="Photo de {{ $user->name }}">
                                @else
                                    {{ $user->initials() }}
                                @endif
                            </span>
                            <label class="admin-field" for="u-avatar">
                                <span>Photo de profil</span>
                                <input id="u-avatar" name="avatar" type="file" accept="image/*">
                            </label>
                            @if ($user->avatarUrl())
                                <label class="admin-toggle-field admin-user-avatar-remove">
                                    <input type="checkbox" name="remove_avatar" value="1">
                                    <span>Supprimer la photo</span>
                                </label>
                            @endif
                        </div>

                        @if ($canManageUserRoles)
                        <div class="admin-user-role-board admin-transfer-board" data-user-role-board>
                            <section class="admin-transfer-board__column admin-transfer-board__column--available">
                                <div class="admin-transfer-board__head">
                                    <h4>Rôles disponibles</h4>
                                    <span>À ajouter</span>
                                </div>
                                <div class="admin-transfer-board__list" data-user-role-list="available" aria-label="Rôles disponibles">
                                    @foreach ($availableRoles as $roleValue => $roleLabel)
                                        <button class="admin-transfer-board__chip" type="button" draggable="true" data-user-role-chip="{{ $roleValue }}">
                                            <i class="fa-solid fa-grip-vertical"></i>
                                            <span>{{ $roleLabel }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </section>

                            <section class="admin-transfer-board__column admin-transfer-board__column--selected">
                                <div class="admin-transfer-board__head">
                                    <h4>Rôles de l'utilisateur</h4>
                                    <span>Actifs</span>
                                </div>
                                <div class="admin-transfer-board__list" data-user-role-list="selected" aria-label="Rôles de l'utilisateur">
                                    <p class="admin-transfer-board__empty" data-user-role-empty>Glisse les rôles ici.</p>
                                    @foreach ($selectedRoles as $roleValue)
                                        @if (isset($roleOptions[$roleValue]))
                                            <button class="admin-transfer-board__chip" type="button" draggable="true" data-user-role-chip="{{ $roleValue }}">
                                                <i class="fa-solid fa-grip-vertical"></i>
                                                <span>{{ $roleOptions[$roleValue] }}</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                                <div data-user-role-inputs></div>
                            </section>
                        </div>
                        @else
                        <div class="admin-user-role-board admin-transfer-board">
                            <section class="admin-transfer-board__column admin-transfer-board__column--selected">
                                <div class="admin-transfer-board__head">
                                    <h4>RÃ´les de l'utilisateur</h4>
                                    <span>Lecture seule</span>
                                </div>
                                <div class="admin-transfer-board__list" aria-label="RÃ´les de l'utilisateur">
                                    @foreach ($selectedRoles as $roleValue)
                                        @if (isset($roleOptions[$roleValue]))
                                            <span class="admin-transfer-board__chip">
                                                <span>{{ $roleOptions[$roleValue] }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        </div>
                        @endif
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>2</span>
                        <div>
                            <h3>Sécurité</h3>
                            <p>Laisse vide si tu ne veux pas changer le mot de passe.</p>
                        </div>
                    </div>

                    <div class="admin-form-grid admin-form-grid--user">
                        <label class="admin-field admin-field--with-action" for="u-password">
                            <span>Nouveau mot de passe</span>
                            <span class="admin-field-action">
                                <input id="u-password" name="password" type="text" placeholder="Nouveau mot de passe" minlength="6" data-password-input>
                                <button class="admin-secondary-button" type="button" data-generate-password>
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    <span>Générer</span>
                                </button>
                            </span>
                        </label>
                    </div>
                </section>

                <div class="admin-form-actions">
                    <a class="admin-secondary-button" href="{{ route('admin.utilisateurs.index') }}">
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

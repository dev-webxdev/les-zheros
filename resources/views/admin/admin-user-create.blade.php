@extends('layouts.admin')

@section('title', 'Créer un utilisateur | Les Zheros')
@section('description', 'Création d\'un utilisateur de la guilde Les Zheros.')
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
            <p>Utilisateurs / Créer</p>
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
            <i class="fa-solid fa-user-plus"></i>
            <h1>Créer un utilisateur</h1>
        </div>

        @if ($errors->any())
            <div class="admin-empty-state">
                <strong>Formulaire incomplet</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <section class="admin-form-card" aria-labelledby="create-user-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="create-user-title">Informations du compte</h2>
                    <p>Renseigne le pseudo, l'email, les rôles et le mot de passe du nouvel utilisateur.</p>
                </div>
            </div>

            <form class="admin-mission-form" action="{{ route('admin.utilisateurs.store') }}" method="post" enctype="multipart/form-data" data-real-form>
                @csrf
                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>1</span>
                        <div>
                            <h3>Identité</h3>
                            <p>Le compte sera ajouté à la liste des utilisateurs.</p>
                        </div>
                    </div>

                    <div class="admin-form-grid admin-form-grid--user">
                        @php($selectedRoles = old('roles', ['member']))
                        @php($availableRoles = collect($roleOptions)->reject(fn (string $label, string $value) => in_array($value, $selectedRoles, true)))

                        <label class="admin-field" for="u-name">
                            <span>Pseudo</span>
                            <input id="u-name" name="name" type="text" value="{{ old('name') }}" placeholder="Ex: Pandawa" required>
                        </label>

                        <label class="admin-field" for="u-email">
                            <span>Email</span>
                            <input id="u-email" name="email" type="email" value="{{ old('email') }}" placeholder="pseudo@leszheros.fr" required>
                        </label>

                        <label class="admin-field admin-field--file" for="u-avatar">
                            <span>Photo de profil</span>
                            <input id="u-avatar" name="avatar" type="file" accept="image/*">
                        </label>

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
                        <input type="hidden" name="roles[]" value="{{ \App\Support\AdminAccess::MEMBER }}">
                        @endif
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>2</span>
                        <div>
                            <h3>Sécurité</h3>
                            <p>Tu peux saisir un mot de passe ou le générer automatiquement.</p>
                        </div>
                    </div>

                    <div class="admin-form-grid admin-form-grid--user">
                        <label class="admin-field admin-field--with-action" for="u-password">
                            <span>Mot de passe</span>
                            <span class="admin-field-action">
                                <input id="u-password" name="password" type="text" placeholder="Mot de passe" minlength="6" required data-password-input>
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
                        <span>Créer</span>
                    </button>
                </div>
            </form>
        </section>
    </section>
</main>
@endsection

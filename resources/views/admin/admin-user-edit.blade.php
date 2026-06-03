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

        <section class="admin-form-card" aria-labelledby="edit-user-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="edit-user-title">Informations du compte</h2>
                    <p>Modifie le pseudo, l'email, les rôles et le mot de passe.</p>
                </div>
            </div>

            @include('admin.partials.user-form')
        </section>
    </section>
</main>
@endsection

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

        <section class="admin-form-card" aria-labelledby="create-user-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="create-user-title">Informations du compte</h2>
                    <p>Renseigne le pseudo, l'email, les rôles et le mot de passe du nouvel utilisateur.</p>
                </div>
            </div>

            @include('admin.partials.user-form')
        </section>
    </section>
</main>
@endsection

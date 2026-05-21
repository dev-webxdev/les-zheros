@extends('layouts.admin')

@section('title', 'Utilisateurs | Les Zheros')
@section('description', 'Administration des utilisateurs de la guilde Les Zheros.')
@php($activeAdmin = 'admin-users')
@php($canDeleteUsers = auth()->user()?->canDeleteInAdminArea('users'))

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Utilisateurs</p>
        </div>

        <div class="admin-actions">
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher..." data-user-search>
            </label>
            <a class="admin-secondary-button" href="{{ route('admin.utilisateurs.trash') }}">
                <i class="fa-regular fa-trash-can"></i>
                <span>Corbeille</span>
            </a>
            <a class="admin-create-button" href="{{ route('admin.utilisateurs.create') }}">
                <i class="fa-solid fa-user-plus"></i>
                <span>Creer un utilisateur</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-users"></i>
            <h1>Utilisateurs</h1>
        </div>

        <div class="admin-table-card">
            <table class="admin-table admin-table--users admin-table--actions-center">
                <thead>
                    <tr>
                        <th>Pseudo</th>
                        <th>Email</th>
                        <th>Rôles</th>
                        <th>Statut</th>
                        <th>Création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
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
                            <td>
                                <span @class(['admin-tag', $user->is_approved ? 'admin-tag--success' : 'admin-tag--warning'])>
                                    {{ $user->is_approved ? 'Validé' : 'À valider' }}
                                </span>
                            </td>
                            <td>{{ $user->created_at?->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    @unless ($user->is_approved)
                                        <form action="{{ route('admin.utilisateurs.approve', $user) }}" method="post" data-real-form>
                                            @csrf
                                            @method('patch')
                                            <button class="admin-action-button admin-action-button--confirm" type="submit" aria-label="Valider {{ $user->name }}" title="Valider">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    @endunless
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.utilisateurs.edit', $user) }}" aria-label="Modifier {{ $user->name }}" title="Modifier">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    @if ($canDeleteUsers)
                                        <form action="{{ route('admin.utilisateurs.destroy', $user) }}" method="post" data-real-form>
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $user->name }} a la corbeille" title="Corbeille">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="admin-empty-state">
                                    <strong>Aucun utilisateur</strong>
                                    <span>Les comptes crees apparaitront ici.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $users])
    </section>
</main>
@endsection


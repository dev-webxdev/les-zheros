@extends('layouts.admin')

@section('title', 'Rôles | Les Zheros')
@section('description', 'Administration des rôles de la guilde Les Zheros.')
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
            <p>Rôles</p>
        </div>

        <div class="admin-actions">
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher...">
            </label>
            <a class="admin-secondary-button" href="{{ route('admin.roles.trash') }}">
                <i class="fa-regular fa-trash-can"></i>
                <span>Corbeille</span>
            </a>
            <a class="admin-create-button" href="{{ route('admin.roles.create') }}">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Créer un rôle</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>Rôles</h1>
        </div>

        <div class="admin-table-card">
            <table class="admin-table admin-table--roles">
                <thead>
                    <tr>
                        <th>Rôle</th>
                        <th>Permissions</th>
                        <th>Utilisateurs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                <div class="admin-role-cell">
                                    <span @class(['admin-tag', $role['tagClass']])>{{ $role['label'] }}</span>
                                </div>
                            </td>
                            <td>
                                @if ($role['hasFullAccess'])
                                    <span class="admin-tag admin-tag--danger">Accès complet</span>
                                @else
                                    <span class="admin-tag" data-permission-count="{{ $role['permissionCount'] }}">
                                        {{ $role['permissionCount'] === 1 ? '1 permission' : $role['permissionCount'].' permissions' }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $role['userCount'] }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <form action="{{ route('admin.roles.preview') }}" method="post" data-real-form>
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $role['key'] }}">
                                        <button @class(['admin-secondary-button', 'admin-role-preview-button', 'is-active' => ($rolePreview ?? null) === $role['key']]) type="submit" title="Tester comme ce role">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>{{ ($rolePreview ?? null) === $role['key'] ? 'En test' : 'Tester' }}</span>
                                        </button>
                                    </form>
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.roles.edit', $role['key']) }}" aria-label="Modifier {{ $role['label'] }}" title="Modifier">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    @if($role['deletable'])
                                        <form action="{{ route('admin.roles.destroy', $role['key']) }}" method="post" data-real-form>
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $role['label'] }} à la corbeille" title="Corbeille">
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
        @include('partials.admin-pagination', ['paginator' => $roles])
    </section>
</main>
@endsection

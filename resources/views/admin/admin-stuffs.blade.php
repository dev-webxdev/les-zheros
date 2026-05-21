@extends('layouts.admin')

@section('title', 'Catalogue stuffs | Les Z-héros')
@section('description', 'Gestion du catalogue de stuffs Les Z-héros.')
@php($activeAdmin = 'admin-stuffs')
@push('scripts')
<script src="{{ asset('assets/js/admin-stuffs.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Catalogue stuffs</p>
        </div>

        <div class="admin-actions">
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher..." data-stuff-search>
            </label>
            <a class="admin-secondary-button" href="{{ route('admin.stuffs.trash') }}">
                <i class="fa-regular fa-trash-can"></i>
                <span>Corbeille</span>
            </a>
            <a class="admin-create-button" href="{{ route('admin.stuffs.create') }}">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Ajouter</span>
            </a>
        </div>
    </header>

    <section class="admin-content admin-stuffs">
        <div class="admin-title">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>Catalogue stuffs</h1>
        </div>

        <div class="admin-table-card">
            <table class="admin-table admin-table--stuffs">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Build</th>
                        <th>Éléments</th>
                        <th>Mode</th>
                        <th>Niveau</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stuffs as $stuff)
                        <tr data-stuff-row data-search="{{ strtolower($stuff->class_label.' '.$stuff->title.' '.implode(' ', $stuff->elements ?? []).' '.$stuff->mode) }}">
                            <td><span class="admin-tag">{{ $stuff->class_label }}</span></td>
                            <td>
                                <div class="admin-announcement-cell">
                                    <strong>{{ $stuff->title }}</strong>
                                </div>
                            </td>
                            <td>{{ implode(' / ', $stuff->elements ?? []) }}</td>
                            <td><span class="admin-tag admin-tag--primary">{{ $stuff->mode }}</span></td>
                            <td>{{ $stuff->levelLabel() }}</td>
                            <td>{{ $stuff->is_published ? 'Publié' : 'Brouillon' }}</td>
                            <td>
                                <div class="admin-row-actions admin-stuff-actions">
                                    <a class="admin-action-button admin-action-button--guide" href="{{ $stuff->dofusbook_url }}" target="_blank" rel="noopener" aria-label="Ouvrir {{ $stuff->title }}" title="Dofusbook"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.stuffs.edit', $stuff) }}" aria-label="Modifier {{ $stuff->title }}" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></a>
                                    @if($canDeleteStuffs)
                                        <form action="{{ route('admin.stuffs.destroy', $stuff) }}" method="post" data-real-form>
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $stuff->title }} à la corbeille" title="Corbeille"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="admin-table-empty-row">
                            <td colspan="7">
                                <div class="admin-empty-state">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <strong>Aucun stuff</strong>
                                    <span>Ajoute un premier build pour alimenter le catalogue.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $stuffs])
    </section>
</main>
@endsection

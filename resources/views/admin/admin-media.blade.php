@extends('layouts.admin')

@section('title', 'Médiathèque | Les Zheros')
@section('description', 'Gestion des images uploadées.')
@php($activeAdmin = 'admin-media')

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation"><i class="fa-solid fa-table-columns"></i></button>
            <span></span>
            <p>Médiathèque</p>
        </div>

        <form class="admin-actions" action="{{ route('admin.mediatheque.index') }}" method="get" data-filter-form>
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" placeholder="Rechercher..." value="{{ $filters['search'] }}">
            </label>
            <select class="admin-filter-select" name="status" onchange="this.form.submit()">
                <option value="all" @selected($filters['status'] === 'all')>Tous les statuts</option>
                <option value="used" @selected($filters['status'] === 'used')>Utilisées</option>
                <option value="unused" @selected($filters['status'] === 'unused')>Inutilisées</option>
            </select>
        </form>
    </header>

    <section class="admin-content">
        <div class="admin-title admin-title--split">
            <div><i class="fa-regular fa-image"></i><h1>Médiathèque</h1></div>
            <p>{{ $stats['total'] }} image(s) · {{ $stats['size'] }} · {{ $stats['unused'] }} supprimable(s)</p>
        </div>

        @include('admin.partials.bulk-actions', [
            'id' => 'media-bulk-form',
            'action' => route('admin.mediatheque.bulk'),
            'actions' => ['delete' => 'Supprimer'],
        ])

        <div class="admin-table-card">
            <table class="admin-table admin-table--media">
                <thead>
                    <tr>
                        <th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="media-bulk-form" aria-label="Tout sélectionner"></th>
                        <th>Aperçu</th>
                        <th>Fichier</th>
                        <th>Dossier</th>
                        <th>Poids</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($images as $image)
                        <tr>
                            <td class="admin-bulk-check">
                                @if($image['deletable'])
                                    <input type="checkbox" name="ids[]" value="{{ $image['path'] }}" form="media-bulk-form" data-bulk-item aria-label="Sélectionner {{ $image['name'] }}">
                                @endif
                            </td>
                            <td>
                                <a class="admin-media-thumb" href="{{ $image['url'] }}" target="_blank" rel="noopener">
                                    <img src="{{ $image['url'] }}" alt="">
                                </a>
                            </td>
                            <td>
                                <strong class="admin-media-name">{{ $image['name'] }}</strong>
                                <span class="admin-media-path">{{ $image['path'] }}</span>
                            </td>
                            <td>{{ $image['directory'] }}</td>
                            <td>{{ $image['size_human'] }}</td>
                            <td>
                                @if($image['deletable'])
                                    <span class="admin-tag admin-tag--success">Inutilisée</span>
                                @elseif($image['used'])
                                    <span class="admin-tag admin-tag--primary">Utilisée</span>
                                @else
                                    <span class="admin-tag">Verrouillée</span>
                                @endif
                                <span class="admin-media-source">{{ $image['source'] }}</span>
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <button class="admin-action-button" type="button" data-copy-media-url="{{ $image['url'] }}" aria-label="Copier le lien de {{ $image['name'] }}" title="Copier le lien"><i class="fa-regular fa-copy"></i></button>
                                    @if($image['deletable'])
                                        <form action="{{ route('admin.mediatheque.destroy') }}" method="post" data-real-form data-confirm-form data-confirm-title="Supprimer cette image ?" data-confirm-text="Cette image n’est pas détectée comme utilisée. Le fichier sera supprimé définitivement." data-confirm-submit="Supprimer">
                                            @csrf
                                            @method('delete')
                                            <input type="hidden" name="path" value="{{ $image['path'] }}">
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer {{ $image['name'] }}" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="admin-empty-state"><i class="fa-regular fa-image"></i><strong>Aucune image</strong><span>Aucune image uploadée ne correspond aux filtres.</span></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $images])
    </section>
</main>
@endsection

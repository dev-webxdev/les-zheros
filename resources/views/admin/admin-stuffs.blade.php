@extends('layouts.admin')

@section('title', 'Catalogue stuffs | Les Z-héros')
@section('description', 'Gestion du catalogue de stuffs Les Z-héros.')
@php($activeAdmin = 'admin-stuffs')
@push('scripts')
<script src="{{ asset('assets/js/admin-stuffs.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Catalogue stuffs'])
        @slot('actions')
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher..." data-stuff-search>
            </label>
            @component('admin.components.button', ['href' => route('admin.stuffs.trash'), 'class' => 'admin-secondary-button', 'icon' => 'fa-regular fa-trash-can', 'label' => 'Corbeille'])@endcomponent
            @component('admin.components.button', ['href' => route('admin.stuffs.create'), 'class' => 'admin-create-button', 'icon' => 'fa-solid fa-circle-plus', 'label' => 'Ajouter'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content admin-stuffs">
        <div class="admin-title">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>Catalogue stuffs</h1>
        </div>

        @include('admin.partials.bulk-actions', [
            'id' => 'stuffs-bulk-form',
            'action' => route('admin.stuffs.bulk'),
            'actions' => $canDeleteStuffs ? ['trash' => 'Mettre en corbeille'] : [],
        ])

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--stuffs'])
                <thead>
                    <tr>
                        @if($canDeleteStuffs)<th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="stuffs-bulk-form" aria-label="Tout sélectionner"></th>@endif
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
                            @if($canDeleteStuffs)<td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $stuff->id }}" form="stuffs-bulk-form" data-bulk-item aria-label="Sélectionner {{ $stuff->title }}"></td>@endif
                            <td>@component('admin.components.badge', ['label' => $stuff->class_label])@endcomponent</td>
                            <td>
                                <div class="admin-announcement-cell">
                                    <strong>{{ $stuff->title }}</strong>
                                </div>
                            </td>
                            <td>{{ implode(' / ', $stuff->elements ?? []) }}</td>
                            <td>@component('admin.components.badge', ['class' => 'admin-tag--primary', 'label' => $stuff->mode])@endcomponent</td>
                            <td>{{ $stuff->levelLabel() }}</td>
                            <td>{{ $stuff->is_published ? 'Publié' : 'Brouillon' }}</td>
                            <td>
                                @component('admin.components.table-actions', ['class' => 'admin-stuff-actions'])
                                    <a class="admin-action-button admin-action-button--guide" href="{{ $stuff->dofusbook_url }}" target="_blank" rel="noopener" aria-label="Ouvrir {{ $stuff->title }}" title="Dofusbook"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.stuffs.edit', $stuff) }}" aria-label="Modifier {{ $stuff->title }}" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></a>
                                    @if($canDeleteStuffs)
                                        <form action="{{ route('admin.stuffs.destroy', $stuff) }}" method="post" data-real-form>
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $stuff->title }} à la corbeille" title="Corbeille"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    @endif
                                @endcomponent
                            </td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => $canDeleteStuffs ? 8 : 7])
                                @component('admin.components.empty-state', ['icon' => 'fa-solid fa-shield-halved', 'title' => 'Aucun stuff', 'text' => 'Ajoute un premier build pour alimenter le catalogue.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $stuffs])
    </section>
</main>
@endsection

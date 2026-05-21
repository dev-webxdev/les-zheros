@extends('layouts.admin')

@section('title', 'Missions | Les Zheros')
@section('description', 'Gestion des missions hebdomadaires de la guilde Les Zheros.')
@php($activeAdmin = 'admin-missions')
@push('scripts')
<script src="{{ asset('assets/js/admin-missions.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Missions</p>
        </div>

        <div class="admin-actions">
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher...">
            </label>
            <a class="admin-secondary-button" href="{{ route('admin.missions.trash') }}">
                <i class="fa-regular fa-trash-can"></i>
                <span>Corbeille</span>
            </a>
            <a class="admin-create-button" href="{{ route('admin.missions.create') }}">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Créer une mission</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title admin-title--split">
            <div>
                <i class="fa-solid fa-scroll"></i>
                <h1>Missions</h1>
            </div>
            <p>Gère les objectifs hebdomadaires et les visuels visibles côté joueurs.</p>
        </div>

        @php($canDeleteMissions = auth()->user()?->canDeleteInAdminArea('missions'))
        @include('admin.partials.bulk-actions', [
            'id' => 'missions-bulk-form',
            'action' => route('admin.missions.bulk'),
            'actions' => $canDeleteMissions ? ['trash' => 'Mettre en corbeille'] : [],
        ])

        <div class="admin-table-card">
            <table class="admin-table admin-table--missions admin-table--actions-center">
                <thead>
                    <tr>
                        @if($canDeleteMissions)<th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="missions-bulk-form" aria-label="Tout sélectionner"></th>@endif
                        <th>Image</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($missions as $mission)
                        <tr>
                            @if($canDeleteMissions)<td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $mission->id }}" form="missions-bulk-form" data-bulk-item aria-label="Sélectionner {{ $mission->title }}"></td>@endif
                            <td><img class="admin-mission-thumb" src="{{ $mission->imageUrl() }}" alt="{{ $mission->title }}"></td>
                            <td>{{ $mission->title }}</td>
                            <td>
                                <span class="admin-tag">{{ $mission->categoryLabel() }}</span>
                                @if ($mission->category === 'songe')
                                    <span class="admin-tag">{{ $mission->dreamTypeLabel() }}</span>
                                    <span class="admin-tag">Palier {{ $mission->dream_floor }}</span>
                                @endif
                            </td>
                            <td>{{ $mission->created_at?->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    @unless(in_array($mission->category, ['songe', 'anomalie', 'regulation'], true))
                                        <a class="admin-action-button admin-action-button--guide" href="{{ $mission->guide ? route('admin.guides.edit', $mission->guide) : route('admin.guides.create', ['mission_id' => $mission->id]) }}" aria-label="{{ $mission->guide ? 'Modifier le guide de '.$mission->title : 'Créer un guide pour '.$mission->title }}" title="{{ $mission->guide ? 'Modifier le guide' : 'Créer un guide' }}">
                                            <i class="fa-solid fa-book-open"></i>
                                        </a>
                                    @endunless
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.missions.edit', $mission) }}" aria-label="Modifier {{ $mission->title }}" title="Modifier">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.missions.destroy', $mission) }}" method="post" data-real-form>
                                        @csrf
                                        @method('delete')
                                        <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer {{ $mission->title }}" title="Supprimer">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canDeleteMissions ? 6 : 5 }}">
                                <div class="admin-empty-state">
                                    <strong>Aucune mission</strong>
                                    <span>Les missions créées apparaîtront ici et sur le front.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $missions])
    </section>
</main>
@endsection

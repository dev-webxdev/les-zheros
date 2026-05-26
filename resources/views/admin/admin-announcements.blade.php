@extends('layouts.admin')

@section('title', 'Annonces | Les Zheros')
@section('description', 'Administration des annonces de la guilde Les Zheros.')
@php($activeAdmin = 'admin-announcements')

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Annonces'])
        @slot('actions')
            @component('admin.components.button', ['href' => route('admin.annonces.trash'), 'class' => 'admin-secondary-button', 'icon' => 'fa-regular fa-trash-can', 'label' => 'Corbeille'])@endcomponent
            @component('admin.components.button', ['href' => route('admin.annonces.create'), 'class' => 'admin-create-button', 'icon' => 'fa-solid fa-circle-plus', 'label' => 'Créer une annonce'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title"><i class="fa-solid fa-bullhorn"></i><h1>Annonces</h1></div>

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--announcements admin-table--actions-center'])
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Statut</th>
                        <th>Publication</th>
                        <th>Auteur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        <tr>
                            <td><div class="admin-announcement-cell"><strong>{{ $announcement->title }}</strong><span>{{ $announcement->preview() }}</span></div></td>
                            <td>@component('admin.components.badge', ['class' => $announcement->statusTagClass(), 'label' => $announcement->statusLabel()])@endcomponent</td>
                            <td>{{ $announcement->published_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $announcement->user?->name ?? 'Admin' }}</td>
                            <td>
                                @component('admin.components.table-actions')
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.annonces.edit', $announcement) }}" aria-label="Modifier {{ $announcement->title }}" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></a>
                                    @if($canDeleteAnnouncements)
                                        <form action="{{ route('admin.annonces.destroy', $announcement) }}" method="post" data-real-form>
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $announcement->title }} à la corbeille" title="Corbeille"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    @endif
                                @endcomponent
                            </td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => 5])
                            @component('admin.components.empty-state', ['icon' => 'fa-solid fa-bullhorn', 'title' => 'Aucune annonce', 'text' => 'Crée une annonce pour l’afficher sur l’accueil.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $announcements])
    </section>
</main>
@endsection

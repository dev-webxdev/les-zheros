@extends('layouts.admin')

@section('title', 'Annonces | Les Zheros')
@section('description', 'Administration des annonces de la guilde Les Zheros.')
@php($activeAdmin = 'admin-announcements')

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation"><i class="fa-solid fa-table-columns"></i></button>
            <span></span>
            <p>Annonces</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.annonces.trash') }}"><i class="fa-regular fa-trash-can"></i><span>Corbeille</span></a>
            <a class="admin-create-button" href="{{ route('admin.annonces.create') }}"><i class="fa-solid fa-circle-plus"></i><span>Créer une annonce</span></a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title"><i class="fa-solid fa-bullhorn"></i><h1>Annonces</h1></div>

        <div class="admin-table-card">
            <table class="admin-table admin-table--announcements admin-table--actions-center">
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
                            <td><span @class(['admin-tag', $announcement->statusTagClass()])>{{ $announcement->statusLabel() }}</span></td>
                            <td>{{ $announcement->published_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $announcement->user?->name ?? 'Admin' }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.annonces.edit', $announcement) }}" aria-label="Modifier {{ $announcement->title }}" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></a>
                                    @if($canDeleteAnnouncements)
                                        <form action="{{ route('admin.annonces.destroy', $announcement) }}" method="post" data-real-form>
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $announcement->title }} à la corbeille" title="Corbeille"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="admin-table-empty-row"><td colspan="5"><div class="admin-empty-state"><i class="fa-solid fa-bullhorn"></i><strong>Aucune annonce</strong><span>Crée une annonce pour l’afficher sur l’accueil.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $announcements])
    </section>
</main>
@endsection

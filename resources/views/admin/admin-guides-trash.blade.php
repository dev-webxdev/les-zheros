@extends('layouts.admin')

@section('title', 'Corbeille des guides | Les Zheros')
@section('description', 'Corbeille des guides de missions de la guilde Les Zheros.')
@php($activeAdmin = 'admin-guides')
@php($canForceDeleteGuides = auth()->user()?->canForceDeleteInAdminArea('guides'))

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Guides / Corbeille</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.guides.index') }}">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Retour aux guides</span>
            </a>
            @if($canForceDeleteGuides && $guides->isNotEmpty())
                <form action="{{ route('admin.guides.empty-trash') }}" method="post" data-real-form>
                    @csrf
                    @method('delete')
                    <button class="admin-danger-button" type="submit">
                        <i class="fa-regular fa-trash-can"></i>
                        <span>Vider la corbeille</span>
                    </button>
                </form>
            @endif
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-book-open"></i>
            <h1>Corbeille</h1>
        </div>

        <div class="admin-guide-list">
            @forelse ($guides as $guide)
                <article class="admin-guide-card">
                    <div class="admin-guide-card__media">
                        <img src="{{ $guide->coverUrl() }}" alt="{{ $guide->title }}">
                    </div>
                    <div class="admin-guide-card__body">
                        <div class="admin-guide-card__meta">
                            <span class="admin-tag">{{ $guide->categoryLabel() }}</span>
                            <span>Supprimé le {{ $guide->deleted_at?->translatedFormat('d M Y') }}</span>
                        </div>
                        <h2>{{ $guide->title }}</h2>
                        <p>{{ $guide->summary }}</p>
                    </div>
                    <div class="admin-guide-card__actions">
                        <form action="{{ route('admin.guides.restore', $guide->id) }}" method="post" data-real-form>
                            @csrf
                            @method('patch')
                            <button class="admin-action-button admin-action-button--restore" type="submit" aria-label="Restaurer {{ $guide->title }}" title="Restaurer"><i class="fa-solid fa-rotate-left"></i></button>
                        </form>
                        @if($canForceDeleteGuides)
                            <form action="{{ route('admin.guides.force-delete', $guide->id) }}" method="post" data-real-form>
                                @csrf
                                @method('delete')
                                <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer définitivement {{ $guide->title }}" title="Supprimer définitivement"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="admin-empty-state--panel">
                    <i class="fa-regular fa-trash-can"></i>
                    <strong>Corbeille vide</strong>
                    <span>Aucun guide supprimé pour le moment.</span>
                </div>
            @endforelse
        </div>
        @include('partials.admin-pagination', ['paginator' => $guides])
    </section>
</main>
@endsection

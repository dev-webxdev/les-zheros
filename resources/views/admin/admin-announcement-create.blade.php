@extends('layouts.admin')

@php
    use App\Models\Announcement;
    $activeAdmin = 'admin-announcements';
    $isEdit = $announcement->exists;
    $formStatus = old('status', $announcement->statusForForm());
@endphp

@section('title', ($isEdit ? 'Modifier' : 'Créer').' une annonce | Les Zheros')
@section('description', 'Création et modification d\'une annonce de la guilde Les Zheros.')
@push('scripts')
<script src="{{ asset('assets/js/admin-announcements.js') }}" defer></script>
<script src="{{ asset('assets/js/admin-rich-editor.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation"><i class="fa-solid fa-table-columns"></i></button>
            <span></span>
            <p>Annonces / {{ $isEdit ? 'Modifier' : 'Créer' }}</p>
        </div>
        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.annonces.index') }}"><i class="fa-solid fa-arrow-left"></i><span>Retour aux annonces</span></a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title"><i class="fa-solid fa-bullhorn"></i><h1>{{ $isEdit ? 'Modifier une annonce' : 'Créer une annonce' }}</h1></div>

        <section class="admin-form-card" aria-labelledby="announcement-title">
            <div class="admin-form-head"><div><h2 id="announcement-title">Informations de l'annonce</h2><p>Prépare le titre, le statut de publication et le contenu visible par la guilde.</p></div></div>

            <form class="admin-mission-form" action="{{ $isEdit ? route('admin.annonces.update', $announcement) : route('admin.annonces.store') }}" method="post" data-real-form>
                @csrf
                @if($isEdit)
                    @method('patch')
                @endif
                <section class="admin-form-section">
                    <div class="admin-form-section-title"><span>1</span><div><h3>Publication</h3><p>Une annonce peut rester en brouillon avant sa mise en ligne.</p></div></div>

                    <div class="admin-form-grid admin-form-grid--announcement">
                        <label class="admin-field admin-field--full" for="announcement-name">
                            <span>Titre</span>
                            <input id="announcement-name" name="title" type="text" value="{{ old('title', $announcement->title) }}" placeholder="Ex: Sortie guilde dimanche soir" required>
                        </label>
                        <label class="admin-field" for="announcement-status">
                            <span>Statut</span>
                            <select id="announcement-status" name="status" required data-announcement-status>
                                <option value="draft" @selected($formStatus === 'draft')>Brouillon</option>
                                <option value="published" @selected($formStatus === 'published')>Immédiat</option>
                                <option value="scheduled" @selected($formStatus === 'scheduled')>Programmé</option>
                            </select>
                        </label>
                        <label class="admin-field" for="announcement-tag">
                            <span>Tag</span>
                            <select id="announcement-tag" name="tag" required>
                                @foreach(Announcement::TAGS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('tag', $announcement->tag) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="admin-field" for="announcement-date" data-scheduled-field hidden>
                            <span>Publication</span>
                            <input id="announcement-date" name="published_at" type="datetime-local" value="{{ old('published_at', $announcement->published_at?->format('Y-m-d\TH:i')) }}">
                        </label>
                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="admin-form-section-title"><span>2</span><div><h3>Contenu</h3><p>Écris le message à afficher sur la carte et dans la modale.</p></div></div>
                    <label class="admin-field admin-field--editor" for="announcement-content">
                        <span>Message</span>
                        <div class="admin-rich-editor" data-rich-editor>
                            <div class="admin-rich-editor__toolbar" aria-label="Outils de mise en forme">
                                <button type="button" data-editor-command="bold" title="Gras"><i class="fa-solid fa-bold"></i></button>
                                <button type="button" data-editor-command="italic" title="Italique"><i class="fa-solid fa-italic"></i></button>
                                <button type="button" data-editor-command="underline" title="Souligné"><i class="fa-solid fa-underline"></i></button>
                                <button type="button" data-editor-command="insertUnorderedList" title="Liste"><i class="fa-solid fa-list-ul"></i></button>
                                <button type="button" data-editor-command="formatBlock" data-editor-value="blockquote" title="Citation"><i class="fa-solid fa-quote-right"></i></button>
                                <button type="button" data-editor-link title="Lien"><i class="fa-solid fa-link"></i></button>
                            </div>
                            <div class="admin-rich-editor__surface" contenteditable="true" data-editor-surface aria-label="Message" data-placeholder="Rédige ton annonce...">{!! old('content', $announcement->content) !!}</div>
                            <textarea id="announcement-content" name="content" required data-editor-input hidden>{{ old('content', $announcement->content) }}</textarea>
                        </div>
                    </label>
                </section>

                <div class="admin-form-actions">
                    <a class="admin-secondary-button" href="{{ route('admin.annonces.index') }}"><i class="fa-solid fa-xmark"></i><span>Annuler</span></a>
                    <button class="admin-create-button" type="submit"><i class="fa-solid fa-floppy-disk"></i><span>Enregistrer</span></button>
                </div>
            </form>
        </section>
    </section>
</main>
@endsection

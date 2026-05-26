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
    @component('admin.components.page-header', ['breadcrumb' => 'Annonces / '.($isEdit ? 'Modifier' : 'Créer')])
        @slot('actions')
            @component('admin.components.button', ['href' => route('admin.annonces.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-arrow-left', 'label' => 'Retour aux annonces'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title"><i class="fa-solid fa-bullhorn"></i><h1>{{ $isEdit ? 'Modifier une annonce' : 'Créer une annonce' }}</h1></div>

        @component('admin.components.form-card', [
            'titleId' => 'announcement-title',
            'title' => 'Informations de l\'annonce',
            'description' => 'Prépare le titre, le statut de publication et le contenu visible par la guilde.',
        ])
            <form class="admin-mission-form" action="{{ $isEdit ? route('admin.annonces.update', $announcement) : route('admin.annonces.store') }}" method="post" data-real-form>
                @csrf
                @if($isEdit)
                    @method('patch')
                @endif
                @component('admin.components.form-section', [
                    'number' => 1,
                    'title' => 'Publication',
                    'description' => 'Une annonce peut rester en brouillon avant sa mise en ligne.',
                ])
                    <div class="admin-form-grid admin-form-grid--announcement">
                        @component('admin.components.text-input', [
                            'id' => 'announcement-name',
                            'name' => 'title',
                            'label' => 'Titre',
                            'value' => old('title', $announcement->title),
                            'placeholder' => 'Ex: Sortie guilde dimanche soir',
                            'required' => true,
                            'class' => 'admin-field--full',
                        ])@endcomponent

                        @component('admin.components.select', [
                            'id' => 'announcement-status',
                            'name' => 'status',
                            'label' => 'Statut',
                            'required' => true,
                            'selectAttributes' => 'data-announcement-status',
                        ])
                            <option value="draft" @selected($formStatus === 'draft')>Brouillon</option>
                            <option value="published" @selected($formStatus === 'published')>Immédiat</option>
                            <option value="scheduled" @selected($formStatus === 'scheduled')>Programmé</option>
                        @endcomponent

                        @component('admin.components.select', [
                            'id' => 'announcement-tag',
                            'name' => 'tag',
                            'label' => 'Tag',
                            'required' => true,
                        ])
                            @foreach(Announcement::TAGS as $value => $label)
                                <option value="{{ $value }}" @selected(old('tag', $announcement->tag) === $value)>{{ $label }}</option>
                            @endforeach
                        @endcomponent

                        @component('admin.components.text-input', [
                            'id' => 'announcement-date',
                            'name' => 'published_at',
                            'type' => 'datetime-local',
                            'label' => 'Publication',
                            'value' => old('published_at', $announcement->published_at?->format('Y-m-d\TH:i')),
                            'fieldAttributes' => 'data-scheduled-field hidden',
                        ])@endcomponent
                    </div>
                @endcomponent

                @component('admin.components.form-section', [
                    'number' => 2,
                    'title' => 'Contenu',
                    'description' => 'Écris le message à afficher sur la carte et dans la modale.',
                ])
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
                @endcomponent

                @component('admin.components.form-actions')
                    @component('admin.components.button', ['href' => route('admin.annonces.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-xmark', 'label' => 'Annuler'])@endcomponent
                    @component('admin.components.button', ['type' => 'submit', 'class' => 'admin-create-button', 'icon' => 'fa-solid fa-floppy-disk', 'label' => 'Enregistrer'])@endcomponent
                @endcomponent
            </form>
        @endcomponent
    </section>
</main>
@endsection

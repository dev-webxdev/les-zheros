@extends('layouts.admin')

@php
    $activeAdmin = 'admin-word-mystery';
@endphp

@section('title', 'Modifier '.$difficultyLabel.' '.$monthLabel.' | Les Zheros')
@section('description', 'Modification groupee des mots Mot Mystere.')

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Mot Mystere / Modifier '.$difficultyLabel.' '.$monthLabel])
        @slot('actions')
            @component('admin.components.button', ['href' => route('admin.mot-mystere.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-arrow-left', 'label' => 'Retour'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-key"></i>
            <h1>{{ $difficultyLabel }} - {{ $monthLabel }}</h1>
        </div>

        @component('admin.components.form-card', [
            'titleId' => 'word-mystery-group-title',
            'title' => 'Mots du mois',
            'description' => 'Modifie tous les mots '.$difficultyLabel.' visibles pour ce mois.',
        ])
            <form class="admin-mission-form" action="{{ route('admin.mot-mystere.groups.update', [$month, $difficulty]) }}" method="post" data-real-form>
                @csrf
                @method('patch')
                @if ($errors->any())
                    @php
                        $visibleErrors = collect($errors->all())->unique()->take(6);
                    @endphp
                    <div class="admin-form-error-summary" role="alert">
                        <strong>Impossible d'enregistrer</strong>
                        <ul>
                            @foreach ($visibleErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            @if ($errors->count() > $visibleErrors->count())
                                <li>Corrige les autres champs signales puis reessaie.</li>
                            @endif
                        </ul>
                    </div>
                @endif

                <div class="admin-table-card admin-word-week-card">
                    <table class="admin-table admin-table--word-week">
                        <thead>
                            <tr>
                                <th>Jour</th>
                                <th>Mot</th>
                                <th>Indice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($words as $index => $word)
                                @php
                                    $fieldPrefix = "words.$index";
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ ucfirst($word->active_date->translatedFormat('l')) }}</strong>
                                        <span>{{ $word->active_date->format('d/m/Y') }}</span>
                                        <input type="hidden" name="words[{{ $index }}][id]" value="{{ $word->id }}">
                                    </td>
                                    <td>
                                        <input name="words[{{ $index }}][word]" type="text" value="{{ old("$fieldPrefix.word", $word->word) }}" placeholder="Mot" required>
                                    </td>
                                    <td>
                                        <input name="words[{{ $index }}][hint]" type="text" value="{{ old("$fieldPrefix.hint", $word->hint) }}" placeholder="Indice" required>
                                    </td>
                                </tr>
                            @empty
                                @component('admin.components.table-empty-row', ['colspan' => 3])
                                    @component('admin.components.empty-state', ['icon' => 'fa-solid fa-key', 'title' => 'Aucun mot', 'text' => 'Aucun mot a venir dans ce groupe.'])@endcomponent
                                @endcomponent
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @component('admin.components.form-actions')
                    @component('admin.components.button', ['href' => route('admin.mot-mystere.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-xmark', 'label' => 'Annuler'])@endcomponent
                    <button class="admin-create-button" type="submit" @disabled($words->isEmpty())>
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Enregistrer</span>
                    </button>
                @endcomponent
            </form>
        @endcomponent
    </section>
</main>
@endsection

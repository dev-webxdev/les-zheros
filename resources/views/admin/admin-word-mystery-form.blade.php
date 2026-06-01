@extends('layouts.admin')

@php
    $isEdit = $word->exists;
    $activeAdmin = 'admin-word-mystery';
    $weekDays = $weekDays ?? [];
    $weekWords = $weekWords ?? collect();
    $isCompactGeneration = ! $isEdit && count($weekDays) > 45;
@endphp

@section('title', ($isEdit ? 'Modifier' : 'Ajouter').' un mot | Les Zheros')
@section('description', 'Gestion d un mot du jeu Mot Mystere.')

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Mot Mystere / '.($isEdit ? 'Modifier' : 'Ajouter')])
        @slot('actions')
            @if(! $isEdit && isset($weekStart))
                <a class="admin-create-button" href="{{ route('admin.mot-mystere.create', ['semaine' => $weekStart->format('Y-m-d'), 'generer' => 'week']) }}">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Generer la semaine</span>
                </a>
                <a class="admin-create-button" href="{{ route('admin.mot-mystere.create', ['semaine' => $weekStart->format('Y-m-d'), 'generer' => 'month']) }}">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span>Generer le mois</span>
                </a>
                <a class="admin-create-button" href="{{ route('admin.mot-mystere.create', ['semaine' => $weekStart->format('Y-m-d'), 'generer' => 'six_months']) }}">
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span>Generer 6 mois</span>
                </a>
                @component('admin.components.button', ['href' => route('admin.mot-mystere.create', ['semaine' => $weekStart->subWeek()->format('Y-m-d')]), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-chevron-left', 'label' => 'Semaine avant'])@endcomponent
                @component('admin.components.button', ['href' => route('admin.mot-mystere.create', ['semaine' => $weekStart->addWeek()->format('Y-m-d')]), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-chevron-right', 'label' => 'Semaine apres'])@endcomponent
            @endif
            @component('admin.components.button', ['href' => route('admin.mot-mystere.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-arrow-left', 'label' => 'Retour'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-key"></i>
            <h1>{{ $isEdit ? 'Modifier le mot' : 'Ajouter une semaine' }}</h1>
        </div>

        @component('admin.components.form-card', [
            'titleId' => 'word-mystery-form-title',
            'title' => $isEdit ? 'Configuration' : 'Mots du lundi au dimanche',
            'description' => $isEdit ? 'Le mot reste secret cote public. Seul l indice est affiche aux joueurs.' : 'Remplis les mots Facile, Normal et Difficile pour toute la semaine en une seule fois.',
        ])
            <form id="word-mystery-editor-form" class="admin-mission-form" action="{{ $isEdit ? route('admin.mot-mystere.update', $word) : route('admin.mot-mystere.store') }}" method="post" data-real-form>
                @csrf
                @if(! $isEdit && isset($weekStart))
                    <input type="hidden" name="week_start" value="{{ $weekStart->format('Y-m-d') }}">
                @endif
                @if($isEdit)
                    @method('patch')

                    @component('admin.components.form-section', [
                        'number' => 1,
                        'title' => 'Mot du jour',
                        'description' => 'Choisis le mot, son indice et la difficulte associee.',
                    ])
                        <div class="admin-stuff-form-grid">
                            @component('admin.components.text-input', [
                                'id' => 'mystery-word',
                                'name' => 'word',
                                'label' => 'Mot mystere',
                                'value' => old('word', $word->word),
                                'placeholder' => 'Ex: Dofus',
                                'required' => true,
                            ])@endcomponent

                            @component('admin.components.text-input', [
                                'id' => 'mystery-hint',
                                'name' => 'hint',
                                'label' => 'Indice',
                                'value' => old('hint', $word->hint),
                                'placeholder' => 'Indice visible par les joueurs',
                                'required' => true,
                            ])@endcomponent

                            @component('admin.components.select', [
                                'id' => 'mystery-difficulty',
                                'name' => 'difficulty',
                                'label' => 'Difficulte',
                                'required' => true,
                            ])
                                @foreach(\App\Models\WordMysteryWord::DIFFICULTIES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('difficulty', $word->difficulty) === $key)>{{ $label }}</option>
                                @endforeach
                            @endcomponent
                        </div>
                    @endcomponent

                    @component('admin.components.form-section', [
                        'number' => 2,
                        'title' => 'Disponibilite',
                        'description' => 'Une date vide sert de mot de secours pour cette difficulte.',
                    ])
                        <div class="admin-stuff-form-grid">
                            @component('admin.components.text-input', [
                                'id' => 'mystery-date',
                                'name' => 'active_date',
                                'type' => 'date',
                                'label' => 'Date du mot',
                                'value' => old('active_date', $word->active_date?->format('Y-m-d')),
                            ])@endcomponent

                            <label class="admin-field">
                                <span>Statut</span>
                                <label class="admin-toggle">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $word->is_active))>
                                    <span>Actif</span>
                                </label>
                            </label>
                        </div>
                    @endcomponent
                @elseif($isCompactGeneration)
                    @component('admin.components.form-section', [
                        'number' => 1,
                        'title' => 'Generation 6 mois',
                        'description' => 'Apercu compact des mots prepares. Clique sur Enregistrer pour les sauvegarder.',
                    ])
                        <div class="admin-table-card admin-word-generation-card">
                            <table class="admin-table admin-table--word-generation-summary">
                                <thead>
                                    <tr>
                                        <th>Mois</th>
                                        <th>Difficulte</th>
                                        <th>Mots prepares</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(\App\Models\WordMysteryWord::DIFFICULTIES as $difficultyKey => $difficultyLabel)
                                        @foreach(collect($weekDays)->groupBy(fn ($day) => $day->format('Y-m')) as $monthKey => $monthDays)
                                            @php
                                                $monthWords = $monthDays
                                                    ->map(fn ($day) => $weekWords->get($difficultyKey.'|'.$day->format('Y-m-d')))
                                                    ->filter();
                                            @endphp
                                            <tr>
                                                <td><strong>{{ ucfirst($monthDays->first()->translatedFormat('F Y')) }}</strong></td>
                                                <td>@component('admin.components.badge', ['label' => $difficultyLabel])@endcomponent</td>
                                                <td>
                                                    <div class="admin-word-summary-list">
                                                        @foreach($monthWords as $generatedWord)
                                                            <span>
                                                                <strong>{{ $generatedWord->active_date->format('d/m') }}</strong>
                                                                {{ $generatedWord->word }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @foreach(\App\Models\WordMysteryWord::DIFFICULTIES as $difficultyKey => $difficultyLabel)
                            @foreach($weekDays as $dayIndex => $day)
                                @php
                                    $existingWord = $weekWords->get($difficultyKey.'|'.$day->format('Y-m-d'));
                                @endphp
                                @if($existingWord)
                                    <input type="hidden" name="weekly_words[{{ $difficultyKey }}][{{ $dayIndex }}][active_date]" value="{{ $day->format('Y-m-d') }}">
                                    <input type="hidden" name="weekly_words[{{ $difficultyKey }}][{{ $dayIndex }}][word]" value="{{ $existingWord->word }}">
                                    <input type="hidden" name="weekly_words[{{ $difficultyKey }}][{{ $dayIndex }}][hint]" value="{{ $existingWord->hint }}">
                                @endif
                            @endforeach
                        @endforeach
                    @endcomponent
                @else
                    @foreach(\App\Models\WordMysteryWord::DIFFICULTIES as $difficultyKey => $difficultyLabel)
                        @component('admin.components.form-section', [
                            'number' => $loop->iteration,
                            'title' => $difficultyLabel,
                            'description' => 'Les 7 mots '.$difficultyLabel.' de la semaine.',
                        ])
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
                                        @foreach($weekDays as $dayIndex => $day)
                                            @php
                                                $existingWord = $weekWords->get($difficultyKey.'|'.$day->format('Y-m-d'));
                                                $fieldPrefix = "weekly_words.$difficultyKey.$dayIndex";
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ ucfirst($day->translatedFormat('l')) }}</strong>
                                                    <span>{{ $day->format('d/m/Y') }}</span>
                                                    <input type="hidden" name="weekly_words[{{ $difficultyKey }}][{{ $dayIndex }}][active_date]" value="{{ $day->format('Y-m-d') }}">
                                                </td>
                                                <td>
                                                    <input name="weekly_words[{{ $difficultyKey }}][{{ $dayIndex }}][word]" type="text" value="{{ old("$fieldPrefix.word", $existingWord?->word) }}" placeholder="Mot" required>
                                                </td>
                                                <td>
                                                    <input name="weekly_words[{{ $difficultyKey }}][{{ $dayIndex }}][hint]" type="text" value="{{ old("$fieldPrefix.hint", $existingWord?->hint) }}" placeholder="Indice visible par les joueurs" required>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endcomponent
                    @endforeach
                @endif

                @component('admin.components.form-actions')
                    @component('admin.components.button', ['href' => route('admin.mot-mystere.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-xmark', 'label' => 'Annuler'])@endcomponent
                    <button class="admin-create-button" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Enregistrer</span>
                    </button>
                @endcomponent
            </form>
        @endcomponent
    </section>
</main>
@endsection

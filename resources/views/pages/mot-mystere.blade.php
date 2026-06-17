@php
    use App\Models\WordMysteryWord;

    $bodyClass = 'home-page word-mystery-page';
    $activePage = 'mot-mystere';
    $difficultyMeta = [
        'easy' => ['label' => 'Facile', 'icon' => 'fa-regular fa-face-smile', 'copy' => '4 lettres'],
        'normal' => ['label' => 'Normal', 'icon' => 'fa-solid fa-scale-balanced', 'copy' => '6 lettres'],
        'hard' => ['label' => 'Difficile', 'icon' => 'fa-solid fa-skull', 'copy' => '8 lettres'],
    ];
    $wordsByDifficulty = $wordsByDifficulty ?? [];
    $difficulties = [];
    foreach (WordMysteryWord::DIFFICULTIES as $key => $label) {
        $currentWord = $wordsByDifficulty[$key] ?? null;
        $difficulties[$key] = [
            ...($difficultyMeta[$key] ?? ['icon' => 'fa-solid fa-key', 'copy' => 'Mot configure en administration.']),
            'label' => $label,
            'word' => $currentWord,
            'base' => $currentWord?->reward_base,
            'available' => $currentWord !== null,
        ];
    }
    $wordLength = $word ? mb_strlen(\Illuminate\Support\Str::of($word->word)->ascii()->replaceMatches('/[^A-Za-z]/', '')->toString()) : (WordMysteryWord::expectedLength($difficulty) ?? 6);
    $guesses = $attempt?->guesses ?? [];
    $attemptsByDifficulty = $attemptsByDifficulty ?? [];
    $rewardPreviews = $rewardPreviews ?? [];
@endphp

@extends('layouts.front')

@section('title', 'Mot Mystere | Les Zheros')
@section('description', 'Jeu de mot mystere quotidien de la guilde Les Zheros.')

@section('content')
<section class="word-mystery-game" id="mot-mystere-jeu">
    <div class="container">
        <header class="word-mystery-game__intro">
            <span class="section-kicker">Jeu quotidien</span>
            <h1>Mot <span>Mystere</span></h1>
            <p>Choisis une difficulté, lis l'indice, puis trouve le mot en 6 essais maximum.</p>
            <div class="word-mystery-rules-inline">
                <span><i class="fa-solid fa-keyboard"></i> 6 essais</span>
                <span><i class="fa-solid fa-coins"></i> Bonus selon l'essai</span>
                <span><i class="fa-solid fa-calendar-day"></i> 1 gain par jour</span>
            </div>
        </header>

        <section class="word-mystery-steps" aria-labelledby="word-mystery-steps-title">
            <div class="word-mystery-steps__head">
                <span class="section-kicker">Comment ca marche ?</span>
                <h2 id="word-mystery-steps-title">4 etapes simples</h2>
            </div>
            <div class="word-mystery-steps__grid">
                @foreach([
                    ['icon' => 'fa-solid fa-sliders', 'title' => 'Choisis une difficulté', 'text' => 'Facile, Normal ou Difficile selon le risque que tu veux prendre.'],
                    ['icon' => 'fa-regular fa-lightbulb', 'title' => 'Lis l indice', 'text' => 'Chaque mot a un indice visible pour orienter ta recherche.'],
                    ['icon' => 'fa-solid fa-keyboard', 'title' => 'Propose un mot', 'text' => 'Tu as 6 essais. Les lettres bien placees et presentes sont indiquees.'],
                    ['icon' => 'fa-solid fa-coins', 'title' => 'Gagne tes kamas', 'text' => 'Plus tu trouves vite, plus le bonus de recompense est interessant.'],
                ] as $step)
                    <article class="word-mystery-step">
                        <i class="{{ $step['icon'] }}"></i>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    <div class="container word-mystery-game__play-head">
        <span class="section-kicker">Partie du jour</span>
        <h2>Choisis ta difficulté</h2>
    </div>

    <div class="container word-mystery-game__layout">
        <main class="word-mystery-play">
            <div class="word-mystery-difficulty-tabs" aria-label="Difficultes" data-word-mystery-tabs>
                @foreach($difficulties as $key => $item)
                    <a @class(['is-active' => $difficulty === $key, 'is-unavailable' => ! $item['available']]) href="{{ route('mot-mystere', ['difficulte' => $key]) }}" data-word-mystery-tab="{{ $key }}" aria-selected="{{ $difficulty === $key ? 'true' : 'false' }}">
                        <i class="{{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                        <em>{{ $item['copy'] }}</em>
                    </a>
                @endforeach
            </div>

            @foreach($difficulties as $key => $item)
                @php
                    $panelWord = $item['word'];
                    $panelAttempt = $attemptsByDifficulty[$key] ?? null;
                    $panelWordLength = $panelWord ? mb_strlen(\Illuminate\Support\Str::of($panelWord->word)->ascii()->replaceMatches('/[^A-Za-z]/', '')->toString()) : (WordMysteryWord::expectedLength($key) ?? 6);
                    $panelGuesses = $panelAttempt?->guesses ?? [];
                @endphp
                <section data-word-mystery-panel="{{ $key }}" data-word-mystery-length="{{ $panelWordLength }}" @if($difficulty !== $key) hidden @endif>
                    @if($panelWord)
                        <div class="word-mystery-hint">
                            <span>Indice</span>
                            <strong>{{ $panelWord->hint }}</strong>
                        </div>

                        <div class="word-mystery-grid" style="--word-size: {{ $panelWordLength }}">
                            @for($row = 0; $row < 6; $row++)
                                @php($guessRow = $panelGuesses[$row] ?? null)
                                <div class="word-mystery-row">
                                    @for($col = 0; $col < $panelWordLength; $col++)
                                        @php($letter = $guessRow ? mb_strtoupper(mb_substr($guessRow['word'], $col, 1)) : '')
                                        <span @class([
                                            'is-correct' => ($guessRow['result'][$col] ?? null) === 'correct',
                                            'is-present' => ($guessRow['result'][$col] ?? null) === 'present',
                                            'is-absent' => ($guessRow['result'][$col] ?? null) === 'absent',
                                        ])>{{ $letter }}</span>
                                    @endfor
                                </div>
                            @endfor
                        </div>

                        @auth
                            @if($panelAttempt?->has_won)
                                <div class="word-mystery-result is-win">
                                    <i class="fa-solid fa-trophy"></i>
                                    <span>Mot trouve en {{ $panelAttempt->attempts_count }} essai(s). Gain en attente : {{ number_format($panelAttempt->reward_earned, 0, ',', ' ') }} kamas.</span>
                                </div>
                            @elseif($panelAttempt?->hasLost())
                                <div class="word-mystery-result is-lost">
                                    <i class="fa-solid fa-hourglass-end"></i>
                                    <span>Les 6 essais sont utilises. Reviens demain pour retenter ta chance.</span>
                                </div>
                            @elseif($hasCompletedToday)
                                <div class="word-mystery-result is-lost">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>{{ $hasWonToday ? "Tu as deja gagne une recompense aujourd'hui. La prochaine sera disponible demain." : "Tu as deja termine ta partie du jour. Reviens demain pour retenter ta chance." }}</span>
                                </div>
                            @else
                                <form class="word-mystery-form" action="{{ route('mot-mystere.submit') }}" method="post" data-real-form data-word-mystery-form>
                                    @csrf
                                    <input type="hidden" name="difficulty" value="{{ $key }}">
                                    <label for="word-mystery-guess-{{ $key }}">Proposition</label>
                                    <div>
                                        <input id="word-mystery-guess-{{ $key }}" name="guess" value="{{ old('difficulty') === $key ? old('guess') : '' }}" minlength="{{ $panelWordLength }}" maxlength="{{ $panelWordLength }}" data-word-mystery-input autocomplete="off" required>
                                        <button class="btn btn--primary" type="submit"><i class="fa-solid fa-arrow-right"></i><span>Valider</span></button>
                                    </div>
                                    <p>{{ 6 - ($panelAttempt?->attempts_count ?? 0) }} essai(s) restant(s). Mot de {{ $panelWordLength }} lettres.</p>
                                </form>
                            @endif
                        @else
                            <div class="word-mystery-login-callout">
                                <strong>Connecte-toi pour jouer</strong>
                                <span>Les essais et les gains sont enregistres cote serveur.</span>
                                <a class="btn btn--primary" href="{{ route('connexion') }}"><i class="fa-solid fa-right-to-bracket"></i><span>Connexion</span></a>
                            </div>
                        @endauth
                    @else
                        <div class="word-mystery-login-callout">
                            <strong>Aucun mot actif aujourd'hui</strong>
                            <span>La synchronisation automatique prepare les mots. Reviens dans quelques instants si besoin.</span>
                        </div>
                    @endif
                </section>
            @endforeach
        </main>

        <aside class="word-mystery-side" data-word-mystery-rewards-wrap>
            @foreach($difficulties as $key => $item)
                <section data-word-mystery-rewards="{{ $key }}" @if($difficulty !== $key) hidden @endif>
                    <span class="section-kicker">Gains possibles</span>
                    <h2>{{ $item['label'] }}</h2>
                    <p>Le gain part d'une base, puis applique le bonus de l'essai reussi.</p>
                    <div class="word-mystery-reward-list">
                        @forelse($rewardPreviews[$key] ?? [] as $row)
                            <div>
                                <span>{{ $row['label'] }}</span>
                                <strong>{{ number_format($row['amount'], 0, ',', ' ') }} kamas</strong>
                            </div>
                        @empty
                            <div>
                                <span>Recompense</span>
                                <strong>Indisponible</strong>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </aside>
    </div>
</section>
@endsection

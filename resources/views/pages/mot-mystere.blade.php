@php
    use App\Models\WordMysteryWord;

    $bodyClass = 'home-page word-mystery-page';
    $activePage = 'mot-mystere';
    $difficulties = [
        'easy' => [
            'label' => 'Facile',
            'icon' => 'fa-regular fa-face-smile',
            'copy' => 'Mot plus simple, indice plus evident, ideal pour jouer rapidement.',
            'base' => 10000,
        ],
        'normal' => [
            'label' => 'Normal',
            'icon' => 'fa-solid fa-scale-balanced',
            'copy' => 'Mot equilibre, indice standard, bon compromis entre risque et recompense.',
            'base' => 25000,
        ],
        'hard' => [
            'label' => 'Difficile',
            'icon' => 'fa-solid fa-skull',
            'copy' => 'Mot plus complique, indice moins evident, pour les vrais connaisseurs.',
            'base' => 50000,
        ],
    ];
    $wordLength = $word ? mb_strlen(\Illuminate\Support\Str::of($word->word)->ascii()->replaceMatches('/[^A-Za-z]/', '')->toString()) : 5;
    $guesses = $attempt?->guesses ?? [];
@endphp

@extends('layouts.front')

@section('title', 'Mot Mystere | Les Zheros')
@section('description', 'Jeu de mot mystere quotidien de la guilde Les Zheros.')

@section('content')
<section class="page-hero word-mystery-hero">
    <div class="container word-mystery-hero__grid">
        <div class="page-hero__content">
            <span class="section-kicker">Jeu quotidien</span>
            <h1>Mot <span>Mystere</span></h1>
            <p>Dechiffre le mot du jour, choisis ta difficulte et tente de gagner des kamas.</p>
            <div class="home-hero-actions">
                <a class="btn btn--primary" href="#mot-mystere-jeu"><i class="fa-solid fa-play"></i><span>Jouer maintenant</span></a>
                <a class="btn btn--outline" href="#mot-mystere-regles"><i class="fa-solid fa-list-check"></i><span>Voir les regles</span></a>
            </div>
        </div>

        <div class="word-mystery-board-preview" aria-hidden="true">
            @foreach(['MOTUS', 'DOFUS', 'KAMAS', 'SORTS', 'GUILDE', 'BOSS'] as $index => $previewWord)
                <div class="word-mystery-preview-row">
                    @foreach(mb_str_split($previewWord) as $letterIndex => $letter)
                        <span @class([
                            'is-correct' => $index === 1,
                            'is-present' => $index === 2 && $letterIndex < 2,
                            'is-empty' => $index > 2,
                        ])>{{ $index > 2 ? '' : $letter }}</span>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="word-mystery-section" id="mot-mystere-jeu">
    <div class="container word-mystery-play-grid">
        <div class="word-mystery-panel">
            <span class="section-kicker">Partie du jour</span>
            <h2>Choisis ta difficulte</h2>
            <div class="word-mystery-difficulty-tabs" aria-label="Difficultes">
                @foreach($difficulties as $key => $item)
                    <a @class(['is-active' => $difficulty === $key]) href="{{ route('mot-mystere', ['difficulte' => $key]) }}">
                        <i class="{{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            @if($word)
                <div class="word-mystery-hint">
                    <span>Indice</span>
                    <strong>{{ $word->hint }}</strong>
                </div>

                <div class="word-mystery-grid" style="--word-size: {{ $wordLength }}">
                    @for($row = 0; $row < 6; $row++)
                        @php($guessRow = $guesses[$row] ?? null)
                        <div class="word-mystery-row">
                            @for($col = 0; $col < $wordLength; $col++)
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
                    @if($attempt?->has_won)
                        <div class="word-mystery-result is-win">
                            <i class="fa-solid fa-trophy"></i>
                            <span>Mot trouve en {{ $attempt->attempts_count }} essai(s). Gain en attente: {{ number_format($attempt->reward_earned, 0, ',', ' ') }} kamas.</span>
                        </div>
                    @elseif($attempt?->hasLost())
                        <div class="word-mystery-result is-lost">
                            <i class="fa-solid fa-hourglass-end"></i>
                            <span>Les 6 essais sont utilises. Reviens demain pour retenter ta chance.</span>
                        </div>
                    @elseif($hasWonToday)
                        <div class="word-mystery-result is-lost">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Tu as deja gagne une recompense aujourd hui. La prochaine recompense sera disponible demain.</span>
                        </div>
                    @else
                        <form class="word-mystery-form" action="{{ route('mot-mystere.submit') }}" method="post" data-real-form>
                            @csrf
                            <input type="hidden" name="difficulty" value="{{ $difficulty }}">
                            <label for="word-mystery-guess">Proposition</label>
                            <div>
                                <input id="word-mystery-guess" name="guess" value="{{ old('guess') }}" maxlength="{{ $wordLength }}" autocomplete="off" required>
                                <button class="btn btn--primary" type="submit"><i class="fa-solid fa-arrow-right"></i><span>Valider</span></button>
                            </div>
                            <p>{{ 6 - ($attempt?->attempts_count ?? 0) }} essai(s) restant(s). Mot de {{ $wordLength }} lettres.</p>
                        </form>
                    @endif
                @else
                    <div class="word-mystery-login-callout">
                        <strong>Connecte-toi pour jouer</strong>
                        <span>Les essais et les gains sont enregistres cote serveur pour eviter les abus.</span>
                        <a class="btn btn--primary" href="{{ route('connexion') }}"><i class="fa-solid fa-right-to-bracket"></i><span>Connexion</span></a>
                    </div>
                @endauth
            @else
                <div class="word-mystery-login-callout">
                    <strong>Aucun mot actif</strong>
                    <span>Ajoute un mot du jour en base pour cette difficulte afin de lancer la partie.</span>
                </div>
            @endif
        </div>

        <aside class="word-mystery-panel word-mystery-reward-panel">
            <span class="section-kicker">Gains possibles</span>
            <h2>{{ WordMysteryWord::DIFFICULTIES[$difficulty] ?? 'Normal' }}</h2>
            <p>La recompense depend de la difficulte et du nombre d essais utilises.</p>
            <div class="word-mystery-reward-list">
                @forelse($rewardPreview as $row)
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
        </aside>
    </div>
</section>

<section class="word-mystery-section">
    <div class="container">
        <div class="word-mystery-section-head">
            <span class="section-kicker">Comment ca marche ?</span>
            <h2>4 etapes simples</h2>
        </div>
        <div class="word-mystery-step-grid">
            @foreach([
                ['icon' => 'fa-solid fa-sliders', 'title' => 'Choisis une difficulte'],
                ['icon' => 'fa-regular fa-lightbulb', 'title' => 'Lis l indice'],
                ['icon' => 'fa-solid fa-keyboard', 'title' => 'Propose jusqu a 6 mots'],
                ['icon' => 'fa-solid fa-coins', 'title' => 'Gagne tes kamas si tu trouves'],
            ] as $step)
                <article class="word-mystery-info-card">
                    <i class="{{ $step['icon'] }}"></i>
                    <h3>{{ $step['title'] }}</h3>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="word-mystery-section">
    <div class="container">
        <div class="word-mystery-section-head">
            <span class="section-kicker">Difficultes & recompenses</span>
            <h2>Choisis ton risque</h2>
        </div>
        <div class="word-mystery-card-grid">
            @foreach($difficulties as $key => $item)
                <article class="word-mystery-info-card word-mystery-info-card--{{ $key }}">
                    <i class="{{ $item['icon'] }}"></i>
                    <h3>{{ $item['label'] }}</h3>
                    <p>{{ $item['copy'] }}</p>
                    <strong>{{ number_format($item['base'], 0, ',', ' ') }} kamas</strong>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="word-mystery-section" id="mot-mystere-regles">
    <div class="container word-mystery-rules-grid">
        <div class="word-mystery-panel">
            <span class="section-kicker">Recompense quotidienne</span>
            <h2>Un gain par jour</h2>
            <ul class="word-mystery-list">
                <li>Un joueur peut gagner une recompense une seule fois par jour.</li>
                <li>Le mot peut changer chaque jour.</li>
                <li>La recompense depend de la difficulte jouee.</li>
                <li>Si le joueur echoue, il peut rejouer le lendemain.</li>
                <li>Le suivi cote backend evite les abus.</li>
            </ul>
        </div>
        <div class="word-mystery-panel">
            <span class="section-kicker">Regles</span>
            <h2>Cadre du jeu</h2>
            <ul class="word-mystery-list">
                <li>6 essais maximum.</li>
                <li>1 indice disponible.</li>
                <li>1 gain maximum par jour et par joueur.</li>
                <li>Le gain est valide seulement si le mot est trouve.</li>
                <li>Les tentatives et victoires sont enregistrees cote backend.</li>
                <li>Les mots, indices et recompenses sont prevus en base.</li>
            </ul>
        </div>
    </div>
</section>

<section class="word-mystery-final-cta">
    <div class="container">
        <div class="word-mystery-panel">
            <span class="section-kicker">Defi du jour</span>
            <h2>Pret a relever le defi du jour ?</h2>
            <p>Choisis ta difficulte et tente de repartir avec des kamas.</p>
            <a class="btn btn--primary" href="#mot-mystere-jeu"><i class="fa-solid fa-play"></i><span>Jouer au Mot Mystere</span></a>
        </div>
    </div>
</section>
@endsection

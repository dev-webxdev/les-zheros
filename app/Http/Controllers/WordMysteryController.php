<?php

namespace App\Http\Controllers;

use App\Models\WordMysteryWord;
use App\Services\WordMysteryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class WordMysteryController extends Controller
{
    public function __construct(private readonly WordMysteryService $wordMystery)
    {
    }

    public function show(Request $request): View
    {
        $difficulty = $this->wordMystery->validDifficulty((string) $request->query('difficulte', 'normal'));
        $wordsByDifficulty = $this->wordMystery->wordsOfTheDay();

        $word = $wordsByDifficulty[$difficulty] ?? null;

        if (! $word && $wordsByDifficulty !== []) {
            $difficulty = array_key_first($wordsByDifficulty);
            $word = $wordsByDifficulty[$difficulty];
        }

        $attempt = $word && $request->user()
            ? $this->wordMystery->currentAttempt($request->user(), $word)
            : null;
        $attemptsByDifficulty = [];
        $rewardPreviews = [];

        foreach ($wordsByDifficulty as $key => $dayWord) {
            $attemptsByDifficulty[$key] = $request->user()
                ? $this->wordMystery->currentAttempt($request->user(), $dayWord)
                : null;
            $rewardPreviews[$key] = $this->wordMystery->rewardRows($dayWord);
        }

        return view('pages.mot-mystere', [
            'difficulty' => $difficulty,
            'word' => $word,
            'wordsByDifficulty' => $wordsByDifficulty,
            'attempt' => $attempt,
            'attemptsByDifficulty' => $attemptsByDifficulty,
            'hasWonToday' => $request->user() ? $this->wordMystery->hasWonToday($request->user()) : false,
            'rewardPreview' => $word ? $this->wordMystery->rewardRows($word) : [],
            'rewardPreviews' => $rewardPreviews,
        ]);
    }

    public function submit(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'difficulty' => ['required', Rule::in(array_keys(WordMysteryWord::DIFFICULTIES))],
            'guess' => ['required', 'string', 'max:40', 'regex:/^[\pL\'-]+$/u'],
        ], [
            'guess.regex' => 'Le mot propose ne peut contenir que des lettres.',
        ]);

        $word = $this->wordMystery->wordOfTheDay($validated['difficulty']);

        if (! $word) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'title' => 'Mot indisponible',
                    'message' => 'Aucun mot actif n est configure pour cette difficulte aujourd hui.',
                    'type' => 'warning',
                ], 404);
            }

            return back()->with('toast', [
                'title' => 'Mot indisponible',
                'text' => 'Aucun mot actif n est configure pour cette difficulte aujourd hui.',
                'type' => 'warning',
            ]);
        }

        try {
            $result = $this->wordMystery->submitGuess($request->user(), $word, $validated['guess']);
        } catch (InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return $this->guessErrorJsonResponse($exception, $word);
            }

            return $this->guessErrorResponse($exception, $word);
        }

        $hasWon = $result['has_won'];
        $attemptsCount = $result['attempts_count'];
        $reward = $result['reward'];

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'difficulty' => $word->difficulty,
                'word_length' => mb_strlen($this->wordMystery->normalizeWord($word->word)),
                'guesses' => $result['attempt']->guesses ?? [],
                'attempts_count' => $attemptsCount,
                'remaining' => max(WordMysteryService::MAX_ATTEMPTS - $attemptsCount, 0),
                'has_won' => $hasWon,
                'has_lost' => ! $hasWon && $attemptsCount >= WordMysteryService::MAX_ATTEMPTS,
                'reward' => $reward,
                'title' => $hasWon ? 'Mot trouve' : ($attemptsCount >= WordMysteryService::MAX_ATTEMPTS ? 'Partie terminee' : 'Essai enregistre'),
                'message' => $hasWon
                    ? 'Mot trouve en '.$attemptsCount.' essai(s). Gain en attente : '.number_format($reward, 0, ',', ' ').' kamas.'
                    : ($attemptsCount >= WordMysteryService::MAX_ATTEMPTS ? 'Les 6 essais sont utilises. Reviens demain pour retenter ta chance.' : 'Continue, il te reste '.(WordMysteryService::MAX_ATTEMPTS - $attemptsCount).' essai(s).'),
                'type' => $hasWon ? 'success' : ($attemptsCount >= WordMysteryService::MAX_ATTEMPTS ? 'warning' : 'success'),
            ]);
        }

        return redirect()
            ->route('mot-mystere', ['difficulte' => $word->difficulty])
            ->with('toast', [
                'title' => $hasWon ? 'Mot trouve' : ($attemptsCount >= WordMysteryService::MAX_ATTEMPTS ? 'Partie terminee' : 'Essai enregistre'),
                'text' => $hasWon
                    ? 'Bravo, recompense en attente: '.number_format($reward, 0, ',', ' ').' kamas.'
                    : ($attemptsCount >= WordMysteryService::MAX_ATTEMPTS ? 'Tu pourras retenter ta chance demain.' : 'Continue, il te reste '.(WordMysteryService::MAX_ATTEMPTS - $attemptsCount).' essai(s).'),
                'type' => $hasWon ? 'success' : ($attemptsCount >= WordMysteryService::MAX_ATTEMPTS ? 'warning' : 'success'),
            ]);
    }

    private function guessErrorResponse(InvalidArgumentException $exception, WordMysteryWord $word): RedirectResponse
    {
        $message = $exception->getMessage();

        if (str_starts_with($message, 'length:')) {
            return back()->withInput()->with('toast', [
                'title' => 'Longueur incorrecte',
                'text' => 'Le mot mystere contient '.substr($message, 7).' lettres.',
                'type' => 'warning',
            ]);
        }

        $errors = [
            'already_won_today' => ['Gain deja obtenu', 'Tu as deja gagne une recompense aujourd hui. Reviens demain pour un nouveau gain.'],
            'already_won_word' => ['Partie terminee', 'Tu as deja trouve le mot du jour.'],
            'attempts_used' => ['Partie terminee', 'Tes 6 essais sont deja utilises.'],
        ];

        [$title, $text] = $errors[$message] ?? ['Action impossible', 'La proposition n a pas pu etre enregistree.'];

        return redirect()
            ->route('mot-mystere', ['difficulte' => $word->difficulty])
            ->with('toast', [
                'title' => $title,
                'text' => $text,
                'type' => 'warning',
            ]);
    }

    private function guessErrorJsonResponse(InvalidArgumentException $exception, WordMysteryWord $word): JsonResponse
    {
        $message = $exception->getMessage();

        if (str_starts_with($message, 'length:')) {
            return response()->json([
                'ok' => false,
                'title' => 'Longueur incorrecte',
                'message' => 'Le mot mystere contient '.substr($message, 7).' lettres.',
                'type' => 'warning',
            ], 422);
        }

        $errors = [
            'already_won_today' => ['Gain deja obtenu', 'Tu as deja gagne une recompense aujourd hui. Reviens demain pour un nouveau gain.'],
            'already_won_word' => ['Partie terminee', 'Tu as deja trouve le mot du jour.'],
            'attempts_used' => ['Partie terminee', 'Tes 6 essais sont deja utilises.'],
        ];

        [$title, $text] = $errors[$message] ?? ['Action impossible', 'La proposition n a pas pu etre enregistree.'];

        return response()->json([
            'ok' => false,
            'title' => $title,
            'message' => $text,
            'type' => 'warning',
            'difficulty' => $word->difficulty,
        ], 422);
    }
}

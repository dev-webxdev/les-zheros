<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('pages.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $this->messageForStatus($status)]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('toast', [
                'title' => 'Lien envoyé',
                'text' => config('mail.default') === 'log'
                    ? 'En local, le lien est dans storage/logs/laravel.log.'
                    : 'Si ce compte existe, un lien de réinitialisation vient de partir par email.',
                'type' => 'success',
            ]);
    }

    private function messageForStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Aucun compte ne correspond à cette adresse email.',
            Password::RESET_THROTTLED => 'Un lien vient déjà d’être envoyé. Tu peux réessayer dans quelques secondes.',
            default => 'Impossible d’envoyer le lien pour le moment. Réessaie dans quelques instants.',
        };
    }
}

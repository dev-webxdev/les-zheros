<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('pages.reset-password', [
            'request' => $request,
            'token' => $token,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(6)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request): void {
                $user->forceFill([
                    'password' => Hash::make($request->string('password')->toString()),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $this->messageForStatus($status)]);
        }

        return redirect()
            ->route('connexion')
            ->with('toast', [
                'title' => 'Mot de passe modifié',
                'text' => 'Tu peux maintenant te connecter avec ton nouveau mot de passe.',
                'type' => 'success',
            ]);
    }

    private function messageForStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'Ce lien de réinitialisation est invalide ou expiré.',
            Password::INVALID_USER => 'Aucun compte ne correspond à cette adresse email.',
            default => 'Impossible de modifier le mot de passe pour le moment.',
        };
    }
}

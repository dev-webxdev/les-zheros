<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('pages.login', [
            'authMode' => 'login',
        ]);
    }

    public function register(): View
    {
        return view('pages.login', [
            'authMode' => 'register',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Ces identifiants ne correspondent a aucun compte.'])
                ->onlyInput('email');
        }

        if (! $request->user()?->is_approved) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Ton compte est en attente de validation par un administrateur.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()
            ->intended(route('profil'))
            ->with('toast', [
                'title' => 'Connexion reussie',
                'text' => 'Bienvenue, tu peux continuer sur ton espace de guilde.',
                'type' => 'success',
            ]);
    }

    public function signup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_approved' => false,
        ]);

        AdminNotifier::notify(
            'users',
            'Nouvelle inscription',
            $user->name.' attend une validation de compte.',
            route('admin.utilisateurs.index'),
            'warning',
        );

        return redirect()
            ->route('connexion')
            ->with('toast', [
                'title' => 'Compte en attente',
                'text' => 'Ton inscription est envoyee. Un administrateur doit valider ton compte.',
                'type' => 'success',
            ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connexion');
    }
}

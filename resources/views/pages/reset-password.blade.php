@extends('layouts.guest')

@section('title', 'Réinitialiser le mot de passe | Les Zheros')
@section('description', 'Choix d’un nouveau mot de passe Les Zheros.')
@php($bodyClass = '')

@section('content')
<main class="login-page">
    <a href="{{ route('accueil') }}" aria-label="Retour à l'accueil">
        <img src="{{ asset('assets/img/logo.png') }}" alt="Les Zheros">
    </a>
    <h1 class="auth-title--compact">Nouveau mot de passe</h1>

    <div class="panel login-card">
        <form action="{{ route('password.store') }}" method="post" class="form-stack" data-auth-form>
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label for="reset-email">Email</label>
                <input id="reset-email" type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="username" required autofocus>
            </div>

            <div class="field">
                <label for="reset-password">Nouveau mot de passe</label>
                <input id="reset-password" type="password" name="password" autocomplete="new-password" minlength="6" required>
            </div>

            <div class="field">
                <label for="reset-password-confirmation">Confirmer le mot de passe</label>
                <input id="reset-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="6" required>
            </div>

            <button type="submit" class="btn btn--primary login-submit">
                <span>Modifier le mot de passe</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>

            <a class="auth-back-link" href="{{ route('connexion') }}">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour à la connexion</span>
            </a>
        </form>
    </div>
</main>
@endsection

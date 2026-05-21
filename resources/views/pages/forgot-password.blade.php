@extends('layouts.guest')

@section('title', 'Mot de passe oublié | Les Zheros')
@section('description', 'Demande de réinitialisation du mot de passe Les Zheros.')
@php($bodyClass = '')

@section('content')
<main class="login-page">
    <a href="{{ route('accueil') }}" aria-label="Retour à l'accueil">
        <img src="{{ asset('assets/img/logo.png') }}" alt="Les Zheros">
    </a>
    <h1 class="auth-title--compact">Mot de passe oublié</h1>

    <div class="panel login-card">
        <form action="{{ route('password.email') }}" method="post" class="form-stack" data-auth-form>
            @csrf

            <div class="field">
                <label for="forgot-email">Email</label>
                <input id="forgot-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </div>

            <button type="submit" class="btn btn--primary login-submit">
                <span>Recevoir le lien</span>
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

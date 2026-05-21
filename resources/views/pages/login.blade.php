@extends('layouts.guest')

@section('title', 'Les Zheros | Guilde Dofus')
@section('description', '')
@php($bodyClass = '')
@php($isRegister = ($authMode ?? 'login') === 'register')

@section('content')
<main class="login-page">
    <img src="{{ asset('assets/img/logo.png') }}" alt="">
    <h1>{{ $isRegister ? 'Inscription' : 'Se connecter' }}</h1>

    <div class="panel login-card">
        <form action="{{ $isRegister ? route('inscription.store') : route('connexion.store') }}" method="post" class="form-stack" data-auth-form>
            @csrf

            @if ($isRegister)
                <div class="field">
                    <label for="register-name">Pseudo (in game)</label>
                    <input id="register-name" type="text" name="name" value="{{ old('name') }}" autocomplete="nickname" required autofocus>
                </div>
            @endif

            <div class="field">
                <label for="auth-email">Email</label>
                <input id="auth-email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required @if (! $isRegister) autofocus @endif>
            </div>

            <div class="field">
                <div class="login-field-head">
                    <label for="auth-password">Mot de passe</label>
                    @if (! $isRegister)
                        <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                    @endif
                </div>
                <input id="auth-password" type="password" name="password" autocomplete="{{ $isRegister ? 'new-password' : 'current-password' }}" @if ($isRegister) minlength="6" @endif required>
            </div>

            @if ($isRegister)
                <div class="field">
                    <label for="password-confirmation">Confirmer le mot de passe</label>
                    <input id="password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="6" required>
                </div>
            @else
                <label class="check">
                    <input type="checkbox" name="remember" value="1">
                    <span>Se souvenir de moi</span>
                </label>
            @endif

            <button type="submit" class="btn btn--primary login-submit">
                <span>{{ $isRegister ? 'Creer mon compte' : 'Se connecter' }}</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>

            <p class="auth-switch">
                @if ($isRegister)
                    Déjà un compte ? <a href="{{ route('connexion') }}">Se connecter</a>
                @else
                    Pas encore de compte ? <a href="{{ route('inscription') }}">S'inscrire</a>
                @endif
            </p>
        </form>
    </div>
</main>
@endsection

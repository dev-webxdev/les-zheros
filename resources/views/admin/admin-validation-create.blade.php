@extends('layouts.admin')

@section('title', 'Ajouter une déclaration | Les Zheros')
@section('description', 'Ajout manuel d\'une déclaration de mission pour la guilde Les Zheros.')
@php($activeAdmin = 'admin-validations')
@push('scripts')
<script src="{{ asset('assets/js/admin-validations.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Validations / Ajouter</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.validations.index') }}">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Retour aux validations</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-circle-check"></i>
            <h1>Ajouter une déclaration</h1>
        </div>

        <section class="admin-form-card" aria-labelledby="validation-create-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="validation-create-title">Déclaration joueur</h2>
                    <p>Ajoute une validation manuellement pour un joueur.</p>
                </div>
            </div>

            <form class="admin-mission-form" action="{{ route('admin.validations.store') }}" method="post" data-real-form>
                @csrf
                @include('admin.partials.validation-form', ['validation' => $validation, 'showTeammateBuilder' => true])
            </form>
        </section>
    </section>
</main>
@endsection

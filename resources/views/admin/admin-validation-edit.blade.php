@extends('layouts.admin')

@section('title', 'Modifier une déclaration | Les Zheros')
@section('description', 'Modification d\'une déclaration de mission pour la guilde Les Zheros.')
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
            <p>Validations / Modifier</p>
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
            <h1>Modifier une déclaration</h1>
        </div>

        <section class="admin-form-card" aria-labelledby="validation-edit-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="validation-edit-title">Déclaration joueur</h2>
                    <p>Modifie la mission, le joueur et le statut.</p>
                </div>
            </div>

            <form class="admin-mission-form" action="{{ route('admin.validations.update', $validation) }}" method="post" data-real-form>
                @csrf
                @method('patch')
                @include('admin.partials.validation-form', ['validation' => $validation])
            </form>
        </section>
    </section>
</main>
@endsection

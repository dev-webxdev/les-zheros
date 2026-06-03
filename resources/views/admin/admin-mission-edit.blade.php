@extends('layouts.admin')

@section('title', 'Modifier une mission | Les Zheros')
@section('description', 'Administration du site de la guilde Les Zheros.')
@php
    $activeAdmin = 'admin-missions';
    $missionCategoryBadges = [
    'donjon' => asset('assets/img/card-mission/type.png'),
    'regulation' => asset('assets/img/card-mission/regulation.png'),
    'expedition' => asset('assets/img/card-mission/expedition.png'),
    'anomalie' => asset('assets/img/card-mission/anomalie.png'),
    'songe' => asset('assets/img/card-mission/songe.png'),
    ];
@endphp
@push('scripts')
<script src="{{ asset('assets/js/admin-missions.js') }}?v={{ filemtime(public_path('assets/js/admin-missions.js')) }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-breadcrumb">
                    <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                        <i class="fa-solid fa-table-columns"></i>
                    </button>
                    <span></span>
                    <p>Missions / Modifier</p>
                </div>

                <div class="admin-actions">
                    <a class="admin-secondary-button" href="{{ route('admin.missions.index') }}">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Retour aux missions</span>
                    </a>
                </div>
            </header>

            <section class="admin-content">
                <div class="admin-title">
                    <i class="fa-solid fa-scroll"></i>
                    <h1>Modifier une mission</h1>
                </div>

                <section class="admin-form-card" id="add-mission" aria-labelledby="add-mission-title">
                    <div class="admin-form-head">
                        <div>
                            <h2 id="add-mission-title">Informations de la mission</h2>
                            <p>Modifie les informations de la mission active sur le cycle hebdomadaire. L'image peut venir de DofusDB, de ton PC ou d'un lien direct.</p>
                        </div>
                    </div>

                    @include('admin.partials.mission-form')
                </section>

            </section>
        </main>
@endsection

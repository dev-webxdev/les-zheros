@extends('layouts.admin')

@section('title', 'Commentaires | Les Zheros')
@section('description', 'Gestion des commentaires de la communaute Les Zheros.')
@php($activeAdmin = 'admin-comments')

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Commentaires</p>
        </div>

        <div class="admin-actions">
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher...">
            </label>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title admin-title--split">
            <div>
                <i class="fa-solid fa-comments"></i>
                <h1>Commentaires</h1>
            </div>
            <p>Retrouve les derniers retours de la communaute a moderer.</p>
        </div>

        <div class="admin-table-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Auteur</th>
                        <th>Page</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5">
                            <div class="admin-empty-state">
                                <i class="fa-solid fa-comments"></i>
                                <strong>Aucun commentaire a moderer</strong>
                                <span>Les commentaires apparaitront ici lorsque le module sera connecte.</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

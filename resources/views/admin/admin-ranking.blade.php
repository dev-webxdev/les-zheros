@extends('layouts.admin')

@section('title', 'Classement | Les Zheros')
@section('description', 'Classement global des missions de la guilde Les Zheros.')
@php($activeAdmin = 'admin-ranking')
@push('scripts')
<script src="{{ asset('assets/js/admin-ranking.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-breadcrumb">
                    <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                        <i class="fa-solid fa-table-columns"></i>
                    </button>
                    <span></span>
                    <p>Classement</p>
                </div>

                <div class="admin-actions">
                    <label class="admin-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" placeholder="Rechercher...">
                    </label>
                </div>
            </header>

            <section class="admin-content">
                <div class="admin-title">
                    <i class="fa-solid fa-trophy"></i>
                    <h1>Classement global</h1>
                </div>

                <div class="admin-table-card">
                    <table class="admin-table admin-table--ranking" data-sortable-ranking>
                        <colgroup>
                            <col class="admin-ranking-col-rank">
                            <col class="admin-ranking-col-player">
                            <col class="admin-ranking-col-small">
                            <col class="admin-ranking-col-small">
                            <col class="admin-ranking-col-points">
                            <col class="admin-ranking-col-points">
                            <col class="admin-ranking-col-points">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Joueur</th>
                                <th>Missions</th>
                                <th>Aides</th>
                                <th><button class="admin-sort-button" type="button" data-sort-ranking="week">Points de la semaine <span>?</span></button></th>
                                <th><button class="admin-sort-button" type="button" data-sort-ranking="month">Points du mois <span>?</span></button></th>
                                <th><button class="admin-sort-button" type="button" data-sort-ranking="total">Points totaux <span>?</span></button></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rankingRows as $row)
                                <tr data-week="{{ $row['week'] }}" data-month="{{ $row['month'] }}" data-total="{{ $row['total'] }}">
                                    <td><span class="admin-rank-badge">#{{ $row['rank'] }}</span></td>
                                    <td>
                                        <div class="admin-user-cell">
                                            <span class="admin-user-avatar">
                                                @if ($row['avatar'])
                                                    <img src="{{ $row['avatar'] }}" alt="Photo de {{ $row['name'] }}">
                                                @else
                                                    {{ $row['initials'] }}
                                                @endif
                                            </span>
                                            <strong>{{ $row['name'] }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $row['missions'] }}</td>
                                    <td>{{ $row['helps'] }}</td>
                                    <td><span class="admin-score admin-score--up">{{ rtrim(rtrim(number_format($row['week'], 2, '.', ''), '0'), '.') }}</span></td>
                                    <td>{{ rtrim(rtrim(number_format($row['month'], 2, '.', ''), '0'), '.') }}</td>
                                    <td><strong>{{ rtrim(rtrim(number_format($row['total'], 2, '.', ''), '0'), '.') }}</strong></td>
                                </tr>
                            @empty
                                <tr data-ranking-empty>
                                    <td colspan="7">
                                        <div class="admin-empty-state">
                                            <strong>Aucun classement</strong>
                                            <span>Les points apparaîtront ici après validation des missions.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.admin-pagination', ['paginator' => $rankingRows])
            </section>
        </main>
@endsection

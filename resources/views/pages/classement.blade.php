@extends('layouts.front')

@section('title', 'Classement | Les Zheros')
@section('description', 'Classement global des missions de la guilde Les Zheros.')
@php($bodyClass = 'home-page ranking-page')
@php($activePage = 'classement')

@section('content')
<section class="page-hero ranking-hero">
            <div class="container page-hero__content">
                <span class="section-kicker">Performance de guilde</span>
                <h1>Classement des <span>missions</span></h1>
                <p>Suivi des participations, aides et points gagnés sur les missions de la guilde.</p>
            </div>
        </section>

        <section class="ranking-board">
            <div class="container">
                <div class="ranking-shell">
                    <div class="ranking-head">
                        <div>
                            <span class="section-kicker">Mai 2026</span>
                            <p>Tri du tableau par points de semaine, mois ou total.</p>
                        </div>
                    </div>

                    <div class="ranking-table-wrap">
                        <table class="ranking-table" data-ranking-table>
                            <thead>
                                <tr>
                                    <th>Rang</th>
                                    <th>Joueur</th>
                                    <th>Missions</th>
                                    <th>Aides</th>
                                    <th><button class="ranking-sort-button" type="button" data-ranking-sort="week">Points semaine <span aria-hidden="true">↓</span></button></th>
                                    <th><button class="ranking-sort-button is-active" type="button" data-ranking-sort="month">Points mois <span aria-hidden="true">↓</span></button></th>
                                    <th><button class="ranking-sort-button" type="button" data-ranking-sort="total">Points totaux <span aria-hidden="true">↓</span></button></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rankingRows as $row)
                                    <tr data-week="{{ $row['week'] }}" data-month="{{ $row['month'] }}" data-total="{{ $row['total'] }}">
                                        <td><span>#{{ $row['rank'] }}</span></td>
                                        <td>
                                            <div class="ranking-player">
                                                <span class="ranking-player__avatar">
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
                                        <td>{{ rtrim(rtrim(number_format($row['week'], 2, '.', ''), '0'), '.') }}</td>
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
                </div>
            </div>
        </section>
@endsection

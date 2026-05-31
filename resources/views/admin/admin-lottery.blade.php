@extends('layouts.admin')

@section('title', 'Loterie | Les Z-héros')
@section('description', 'Gestion de la loterie hebdomadaire de guilde Les Z-héros.')
@php($activeAdmin = 'admin-lottery')
@push('scripts')
<script src="{{ asset('assets/js/admin-lottery.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-breadcrumb">
                    <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                        <i class="fa-solid fa-table-columns"></i>
                    </button>
                    <span></span>
                    <p>Loterie</p>
                </div>

                <div class="admin-actions">
                    <button class="admin-secondary-button" type="button" data-lottery-refresh>
                        <i class="fa-solid fa-rotate"></i>
                        <span>Actualiser</span>
                    </button>
                    <button class="admin-create-button" type="button" data-lottery-draw>
                        <i class="fa-solid fa-dice"></i>
                        <span>Lancer la loterie</span>
                    </button>
                </div>
            </header>

            <script id="lottery-participants-data" type="application/json">@json($lotteryParticipantsByWeek ?? [])</script>
            <script id="lottery-pending-validations-data" type="application/json">@json($lotteryPendingValidationsByWeek ?? [])</script>
            <script id="lottery-settings-data" type="application/json">@json($lotterySettings ?? \App\Models\GuildSetting::lotterySettings())</script>

            <section class="admin-content admin-lottery" data-lottery data-lottery-author="{{ auth()->user()?->name ?? 'Admin' }}">
                <div class="admin-lottery-cycle-bar">
                    <label class="admin-field admin-lottery-week" for="lottery-week">
                        <span>Semaine à tirer</span>
                        <select id="lottery-week" data-lottery-week>
                            @foreach ($lotteryWeeks ?? [] as $week)
                                <option value="{{ $week['value'] }}">{{ $week['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <span class="admin-lottery-status" data-lottery-status>Aucun tirage</span>
                </div>

                <section class="admin-lottery-panel">
                    <div class="admin-lottery-panel__head">
                        <div>
                            <h2>Paramètres du tirage</h2>
                            <p data-lottery-range>{{ $selectedLotteryWeek['label'] ?? 'Cycle en cours' }}</p>
                        </div>
                    </div>

                    <div class="admin-lottery-stats">
                        <article>
                            <span>Participants éligibles</span>
                            <strong data-lottery-eligible>0</strong>
                        </article>
                        <article>
                            <span>Total tickets</span>
                            <strong data-lottery-tickets>0</strong>
                        </article>
                        <article>
                            <span>Dernier tirage</span>
                            <strong data-lottery-last>Aucun tirage</strong>
                        </article>
                    </div>

                    <div class="admin-lottery-result" data-lottery-result hidden></div>
                </section>

                <section class="admin-lottery-panel">
                    <div class="admin-lottery-panel__head">
                        <div>
                            <h2>Participants de la semaine</h2>
                            <p>Les tickets sont calculés avec les points validés et le multiplicateur du barème.</p>
                        </div>
                    </div>

                    <div class="admin-table-card">
                        <table class="admin-table admin-table--lottery">
                            <thead>
                                <tr>
                                    <th>Rang</th>
                                    <th>Joueur</th>
                                    <th>Points validés</th>
                                    <th>Tickets</th>
                                    <th>Missions</th>
                                    <th>Aides</th>
                                </tr>
                            </thead>
                            <tbody data-lottery-participants></tbody>
                        </table>
                    </div>
                    <div class="admin-lottery-pagination" data-lottery-participants-pagination></div>
                </section>

                <section class="admin-lottery-panel">
                    <div class="admin-lottery-panel__head">
                        <div>
                            <h2>Historique des tirages</h2>
                            <p>Les derniers résultats restent visibles pour vérifier les gains distribués.</p>
                        </div>
                    </div>

                    <div class="admin-table-card">
                        <table class="admin-table admin-table--lottery-history">
                            <thead>
                                <tr>
                                    <th>Date tirage</th>
                                    <th>Semaine</th>
                                    <th>#1</th>
                                    <th>#2</th>
                                    <th>#3</th>
                                    <th>Kamas distribués</th>
                                    <th>Tiré par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody data-lottery-history></tbody>
                        </table>
                    </div>
                    <div class="admin-lottery-pagination" data-lottery-history-pagination></div>
                </section>
            </section>
        </main>
@endsection

@section('modals')
<div class="admin-lottery-modal" data-lottery-draw-modal hidden>
        <div class="admin-lottery-modal__backdrop" data-lottery-draw-close></div>
        <section class="admin-lottery-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="lottery-draw-title">
            <div class="admin-lottery-modal__head">
                <div>
                    <span class="admin-lottery-modal__eyebrow">Loterie hebdomadaire</span>
                    <h2 id="lottery-draw-title">Tirage de la loterie</h2>
                    <p data-lottery-draw-state>Mélange des tickets en cours...</p>
                </div>
                <button class="admin-action-button" type="button" data-lottery-draw-close aria-label="Fermer le tirage" hidden>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="admin-lottery-draw-slots" data-lottery-draw-slots>
                <article class="admin-lottery-draw-card admin-lottery-draw-card--gold" data-lottery-draw-slot="0">
                    <span>#1</span>
                    <strong>...</strong>
                    <em>...</em>
                </article>
                <article class="admin-lottery-draw-card admin-lottery-draw-card--silver" data-lottery-draw-slot="1">
                    <span>#2</span>
                    <strong>...</strong>
                    <em>...</em>
                </article>
                <article class="admin-lottery-draw-card admin-lottery-draw-card--bronze" data-lottery-draw-slot="2">
                    <span>#3</span>
                    <strong>...</strong>
                    <em>...</em>
                </article>
            </div>

            <div class="admin-lottery-modal__actions" data-lottery-draw-actions hidden>
                <button class="admin-create-button" type="button" data-lottery-download>
                    <i class="fa-solid fa-download"></i>
                    <span>Télécharger l'image</span>
                </button>
                <button class="admin-secondary-button" type="button" data-lottery-draw-close>Fermer</button>
            </div>
        </section>
    </div>
@endsection

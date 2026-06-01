@extends('layouts.admin')

@section('title', 'Mot Mystere | Les Zheros')
@section('description', 'Gestion du jeu Mot Mystere.')
@php
    $activeAdmin = 'admin-word-mystery';
@endphp

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Mot Mystere'])
        @slot('actions')
            @component('admin.components.button', ['href' => route('admin.mot-mystere.trash'), 'class' => 'admin-secondary-button', 'icon' => 'fa-regular fa-trash-can', 'label' => 'Corbeille'])@endcomponent
            @component('admin.components.button', ['href' => route('admin.mot-mystere.create'), 'class' => 'admin-create-button', 'icon' => 'fa-solid fa-circle-plus', 'label' => 'Ajouter un mot'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-key"></i>
            <h1>Mot Mystere</h1>
        </div>

        <form id="word-mystery-bulk-form" class="admin-bulk-actions" action="{{ route('admin.mot-mystere.bulk') }}" method="post" data-bulk-form>
            @csrf
            <label class="admin-bulk-actions__check">
                <input type="checkbox" name="scope" value="all" data-bulk-filtered-scope>
                <span>Tous les mots visibles ({{ $wordsCount }})</span>
            </label>
            <span data-bulk-count>0 selectionne</span>
            <button class="admin-danger-button" type="submit" name="action" value="trash" disabled data-bulk-submit>
                <i class="fa-regular fa-trash-can"></i>
                <span>Mettre en corbeille</span>
            </button>
        </form>

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--word-mystery admin-table--word-summary'])
                <thead>
                    <tr>
                        <th class="admin-bulk-check"></th>
                        <th>Mois</th>
                        <th>Difficulte</th>
                        <th>Mots</th>
                        <th>Gain</th>
                        <th>Essais</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wordRows as $row)
                        <tr>
                            <td class="admin-bulk-check">
                                <input type="checkbox" form="word-mystery-bulk-form" data-bulk-item data-bulk-values="{{ $row['words']->pluck('id')->implode(',') }}" aria-label="Selectionner {{ $row['difficulty_label'] }} {{ $row['month_label'] }}">
                            </td>
                            <td>
                                <strong>{{ $row['month_label'] }}</strong>
                            </td>
                            <td>@component('admin.components.badge', ['label' => $row['difficulty_label']])@endcomponent</td>
                            <td>
                                <div class="admin-word-summary-list">
                                    @foreach($row['words'] as $word)
                                        <span>
                                            <strong>{{ $word->active_date->format('d/m') }}</strong>
                                            {{ $word->word }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ number_format($row['reward_base'], 0, ',', ' ') }} kamas</td>
                            <td>{{ $row['attempts_count'] }}</td>
                            <td>
                                @component('admin.components.badge', [
                                    'class' => $row['all_active'] ? 'admin-tag--success' : 'admin-tag--warning',
                                    'label' => $row['all_active'] ? 'Actif' : 'A verifier',
                                ])@endcomponent
                            </td>
                            <td>
                                @component('admin.components.table-actions')
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.mot-mystere.groups.edit', [$row['month'], $row['difficulty']]) }}" aria-label="Modifier {{ $row['difficulty_label'] }} {{ $row['month_label'] }}" title="Modifier le groupe">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                @endcomponent
                            </td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => 8])
                            @component('admin.components.empty-state', ['icon' => 'fa-solid fa-key', 'title' => 'Aucun mot a venir', 'text' => 'Genere ou ajoute des mots pour aujourd hui et les prochains jours.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $wordRows])

        <div class="admin-title" style="margin-top: 28px;">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <h1>Historique joueurs</h1>
        </div>

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--word-mystery-history'])
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Mot</th>
                        <th>Difficulte</th>
                        <th>Essais</th>
                        <th>Gain</th>
                        <th>Resultat</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $attempt)
                        @php
                            $historyStatus = $attempt->has_won ? 'Trouve' : ($attempt->hasLost() ? 'Perdu' : 'En cours');
                            $historyStatusClass = $attempt->has_won ? 'admin-tag--success' : ($attempt->hasLost() ? 'admin-tag--danger' : 'admin-tag--warning');
                        @endphp
                        <tr>
                            <td><strong>{{ $attempt->user?->name ?? 'Utilisateur supprime' }}</strong></td>
                            <td><strong>{{ $attempt->word?->word ?? '-' }}</strong></td>
                            <td>{{ $attempt->word?->difficultyLabel() ?? $attempt->difficulty }}</td>
                            <td>{{ $attempt->attempts_count }}</td>
                            <td>{{ $attempt->reward_earned > 0 ? number_format($attempt->reward_earned, 0, ',', ' ').' kamas' : '-' }}</td>
                            <td>@component('admin.components.badge', ['class' => $historyStatusClass, 'label' => $historyStatus])@endcomponent</td>
                            <td>{{ optional($attempt->played_at ?? $attempt->updated_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => 7])
                            @component('admin.components.empty-state', ['icon' => 'fa-solid fa-clock-rotate-left', 'title' => 'Aucun historique', 'text' => 'Les essais des joueurs apparaitront ici.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $history])

        <div class="admin-title" style="margin-top: 28px;">
            <i class="fa-solid fa-coins"></i>
            <h1>Recompenses</h1>
        </div>

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--word-mystery-rewards'])
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Mot</th>
                        <th>Difficulte</th>
                        <th>Essais</th>
                        <th>Gain</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rewards as $reward)
                        <tr>
                            <td><strong>{{ $reward->user?->name ?? 'Utilisateur supprime' }}</strong></td>
                            <td><strong>{{ $reward->attempt?->word?->word ?? '-' }}</strong></td>
                            <td>{{ $reward->attempt?->word?->difficultyLabel() ?? $reward->attempt?->difficulty }}</td>
                            <td>{{ $reward->attempt?->attempts_count ?? '-' }}</td>
                            <td>{{ number_format($reward->amount, 0, ',', ' ') }} kamas</td>
                            @php
                                $rewardStatusClass = match ($reward->status) {
                                'paid' => 'admin-tag--success',
                                'cancelled' => 'admin-tag--danger',
                                default => 'admin-tag--warning',
                                };
                            @endphp
                            <td>@component('admin.components.badge', ['class' => $rewardStatusClass, 'label' => \App\Models\WordMysteryReward::STATUSES[$reward->status] ?? $reward->status])@endcomponent</td>
                            <td>
                                <div class="admin-row-actions">
                                    @foreach(['pending' => 'En attente', 'paid' => 'Payée', 'cancelled' => 'Annulée'] as $status => $label)
                                        @if($reward->status !== $status)
                                            <form action="{{ route('admin.mot-mystere.rewards.update', $reward) }}" method="post" data-real-form>
                                                @csrf
                                                @method('patch')
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button class="admin-secondary-button" type="submit">{{ $label }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                    <form action="{{ route('admin.mot-mystere.rewards.destroy', $reward) }}" method="post" data-real-form>
                                        @csrf
                                        @method('delete')
                                        <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre la recompense a la corbeille" title="Corbeille">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => 7])
                            @component('admin.components.empty-state', ['icon' => 'fa-solid fa-coins', 'title' => 'Aucune recompense', 'text' => 'Les gains des joueurs apparaitront ici.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $rewards])
    </section>
</main>
@endsection

@extends('layouts.admin')

@section('title', 'Mot Mystere | Les Zheros')
@section('description', 'Gestion du jeu Mot Mystere.')
@php($activeAdmin = 'admin-word-mystery')

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Mot Mystere'])
        @slot('actions')
            @component('admin.components.button', ['href' => route('admin.mot-mystere.create'), 'class' => 'admin-create-button', 'icon' => 'fa-solid fa-circle-plus', 'label' => 'Ajouter un mot'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-key"></i>
            <h1>Mot Mystere</h1>
        </div>

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--word-mystery'])
                <thead>
                    <tr>
                        <th>Mot</th>
                        <th>Difficulte</th>
                        <th>Date</th>
                        <th>Gain base</th>
                        <th>Essais</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($words as $word)
                        <tr>
                            <td>
                                <div class="admin-announcement-cell">
                                    <strong>{{ $word->word }}</strong>
                                    <span>{{ $word->hint }}</span>
                                </div>
                            </td>
                            <td>@component('admin.components.badge', ['label' => $word->difficultyLabel()])@endcomponent</td>
                            <td>{{ $word->active_date?->format('d/m/Y') ?? 'Tous les jours' }}</td>
                            <td>{{ number_format($word->reward_base, 0, ',', ' ') }} kamas</td>
                            <td>{{ $word->attempts_count }}</td>
                            <td>{{ $word->is_active ? 'Actif' : 'Inactif' }}</td>
                            <td>
                                @component('admin.components.table-actions')
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.mot-mystere.edit', $word) }}" aria-label="Modifier {{ $word->word }}" title="Modifier">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.mot-mystere.destroy', $word) }}" method="post" data-real-form>
                                        @csrf
                                        @method('delete')
                                        <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Supprimer {{ $word->word }}" title="Supprimer">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endcomponent
                            </td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => 7])
                            @component('admin.components.empty-state', ['icon' => 'fa-solid fa-key', 'title' => 'Aucun mot', 'text' => 'Ajoute un premier mot mystere pour lancer le jeu.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $words])

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
                            @php($rewardStatusClass = match ($reward->status) {
                                'paid' => 'admin-tag--success',
                                'cancelled' => 'admin-tag--danger',
                                default => 'admin-tag--warning',
                            })
                            <td>@component('admin.components.badge', ['class' => $rewardStatusClass, 'label' => \App\Models\WordMysteryReward::STATUSES[$reward->status] ?? $reward->status])@endcomponent</td>
                            <td>
                                <div class="admin-row-actions">
                                    @foreach(['pending' => 'En attente', 'paid' => 'Payee', 'cancelled' => 'Annulee'] as $status => $label)
                                        @if($reward->status !== $status)
                                            <form action="{{ route('admin.mot-mystere.rewards.update', $reward) }}" method="post" data-real-form>
                                                @csrf
                                                @method('patch')
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button class="admin-secondary-button" type="submit">{{ $label }}</button>
                                            </form>
                                        @endif
                                    @endforeach
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

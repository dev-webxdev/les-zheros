@extends('layouts.admin')

@section('title', 'Journal d’activité | Les Zheros')
@section('description', 'Historique des actions admin du site Les Zheros.')
@php($activeAdmin = 'admin-activity')

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Journal d’activité</p>
        </div>

        <div class="admin-actions">
            <form class="admin-actions" action="{{ route('admin.activite.index', ['area' => $filters['area'] === 'all' ? null : $filters['area']]) }}" method="get" data-filter-form>
                <label class="admin-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Rechercher..." data-server-search>
                </label>
                <select class="admin-filter-select" data-activity-area-filter onchange="const next = this.value === 'all' ? '{{ route('admin.activite.index') }}' : '{{ url('/admin/activite') }}/' + encodeURIComponent(this.value); const search = this.form.search.value.trim(); window.location.href = search ? next + '?search=' + encodeURIComponent(search) : next;">
                    <option value="all">Toutes les sections</option>
                    @foreach($areas as $area)
                        <option value="{{ $area }}" @selected($filters['area'] === $area)>{{ ucfirst($area) }}</option>
                    @endforeach
                </select>
            </form>
            @if($logs->total() > 0)
                <form action="{{ route('admin.activite.destroy') }}" method="post" data-real-form data-confirm-form data-confirm-icon="trash" data-confirm-title="Vider le journal d’activité ?" data-confirm-text="Toutes les lignes du journal seront supprimées définitivement." data-confirm-submit="Vider le journal">
                    @csrf
                    @method('delete')
                    <button class="admin-danger-button" type="submit">
                        <i class="fa-regular fa-trash-can"></i>
                        <span>Vider le journal</span>
                    </button>
                </form>
            @endif
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <h1>Journal d’activité</h1>
        </div>

        <div class="admin-table-card">
            <table class="admin-table admin-table--activity">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Auteur</th>
                        <th>Section</th>
                        <th>Action</th>
                        <th>Élément</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td>
                                <div class="admin-user-cell">
                                    <span class="admin-user-avatar">{{ mb_substr($log->actorName(), 0, 2) }}</span>
                                    <strong>{{ $log->actorName() }}</strong>
                                </div>
                            </td>
                            <td><span class="admin-tag admin-tag--neutral">{{ ucfirst($log->area) }}</span></td>
                            <td>
                                <strong>{{ $log->title }}</strong>
                                @if($log->description)
                                    <span>{{ $log->description }}</span>
                                @endif
                            </td>
                            <td>{{ $log->subject_label ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr class="admin-table-empty-row">
                            <td colspan="5">
                                <div class="admin-empty-state">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <strong>Aucune activité</strong>
                                    <span>Les actions admin importantes apparaitront ici.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $logs])
    </section>
</main>
@endsection

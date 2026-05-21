@extends('layouts.admin')

@section('title', 'Notifications | Les Zheros')
@section('description', 'Notifications internes de l’administration Les Zheros.')
@php($activeAdmin = 'admin-notifications')

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Notifications</p>
        </div>

        <div class="admin-actions">
            @if($unreadCount > 0)
                <form action="{{ route('admin.notifications.read-all') }}" method="post" data-real-form>
                    @csrf
                    @method('patch')
                    <button class="admin-secondary-button" type="submit">
                        <i class="fa-solid fa-check-double"></i>
                        <span>Tout marquer lu</span>
                    </button>
                </form>
            @endif
            @if($notifications->total() > 0)
                <form action="{{ route('admin.notifications.destroy') }}" method="post" data-real-form data-confirm-form data-confirm-title="Vider les notifications ?" data-confirm-text="Toutes les notifications internes seront supprimées définitivement." data-confirm-submit="Vider">
                    @csrf
                    @method('delete')
                    <button class="admin-danger-button" type="submit">
                        <i class="fa-regular fa-trash-can"></i>
                        <span>Vider</span>
                    </button>
                </form>
            @endif
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-bell"></i>
            <h1>Notifications</h1>
        </div>

        <div class="admin-table-card">
            <table class="admin-table admin-table--notifications">
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Notification</th>
                        <th>Section</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr @class(['is-unread' => $notification->isUnread()])>
                            <td>
                                <span @class(['admin-tag', $notification->isUnread() ? 'admin-tag--warning' : 'admin-tag--neutral'])>
                                    {{ $notification->isUnread() ? 'Non lue' : 'Lue' }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $notification->title }}</strong>
                                @if($notification->message)
                                    <span>{{ $notification->message }}</span>
                                @endif
                            </td>
                            <td><span class="admin-tag admin-tag--neutral">{{ ucfirst($notification->area) }}</span></td>
                            <td>{{ $notification->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td>
                                @if($notification->url)
                                    <a class="admin-action-button admin-action-button--edit" href="{{ $notification->url }}" title="Ouvrir">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="admin-table-empty-row">
                            <td colspan="5">
                                <div class="admin-empty-state">
                                    <i class="fa-solid fa-bell"></i>
                                    <strong>Aucune notification</strong>
                                    <span>Les nouvelles inscriptions et validations apparaitront ici.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $notifications])
    </section>
</main>
@endsection

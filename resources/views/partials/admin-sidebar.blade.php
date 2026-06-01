@php
    $activeAdmin = $activeAdmin ?? '';
    $adminUser = auth()->user();
    $can = fn (string $area): bool => (bool) $adminUser?->canAccessAdminArea($area);
    $canWordMystery = (bool) ($adminUser?->canAccessAdminArea('word_mystery') || $adminUser?->canAccessAdminPermission('word_mystery.manage'));
@endphp

<aside class="admin-sidebar" aria-label="Navigation administration">
    <a class="admin-brand" href="{{ route('accueil') }}">
        <img src="{{ asset('assets/img/logo.png') }}" alt="">
        <span>Les Zheros</span>
    </a>

    <nav class="admin-nav">
        <a @class(['admin-nav__link', 'is-active' => $activeAdmin === 'admin']) href="{{ route('admin.dashboard') }}">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        @if ($can('announcements') || $can('missions') || $can('validations') || $can('guides') || $can('gallery') || $can('stuffs'))
            <p class="admin-nav__label">Contenus</p>

            @if ($can('announcements'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-announcements')]) href="{{ route('admin.annonces.index') }}">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Annonces</span>
                </a>
            @endif

            @if ($can('missions'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-missions')]) href="{{ route('admin.missions.index') }}">
                    <i class="fa-solid fa-scroll"></i>
                    <span>Missions</span>
                </a>
            @endif

            @if ($can('validations'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-validations')]) href="{{ route('admin.validations.index') }}">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Validations</span>
                </a>
            @endif

            @if ($can('guides'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-guides')]) href="{{ route('admin.guides.index') }}">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Guides</span>
                </a>
            @endif

            @if ($can('gallery'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-gallery')]) href="{{ route('admin.galerie.index') }}">
                    <i class="fa-regular fa-images"></i>
                    <span>Galerie</span>
                </a>
            @endif

            @if ($can('stuffs'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-stuffs')]) href="{{ route('admin.stuffs.index') }}">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Catalogue stuffs</span>
                </a>
            @endif
        @endif

        @if ($can('outings') || $can('lottery') || $canWordMystery || $can('ranking'))
            <p class="admin-nav__label">Activités</p>

            @if ($can('outings'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-sorties')]) href="{{ route('admin.sorties.index') }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Sorties</span>
                </a>
            @endif

            @if ($can('lottery'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-lottery')]) href="{{ route('admin.loterie.index') }}">
                    <i class="fa-solid fa-dice"></i>
                    <span>Loterie</span>
                </a>
            @endif

            @if ($canWordMystery)
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-word-mystery')]) href="{{ route('admin.mot-mystere.index') }}">
                    <i class="fa-solid fa-key"></i>
                    <span>Mot Mystère</span>
                </a>
            @endif

            @if ($can('ranking'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-ranking')]) href="{{ route('admin.classement.index') }}">
                    <i class="fa-solid fa-trophy"></i>
                    <span>Classement</span>
                </a>
            @endif
        @endif

        @if ($can('users') || $can('comments'))
            <p class="admin-nav__label">Communauté</p>

            @if ($can('users'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-users')]) href="{{ route('admin.utilisateurs.index') }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Utilisateurs</span>
                </a>
            @endif

            @if ($can('comments'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-comments')]) href="{{ route('admin.commentaires.index') }}">
                    <i class="fa-solid fa-comments"></i>
                    <span>Commentaires</span>
                </a>
            @endif
        @endif

        @if ($can('roles') || $can('settings') || $can('activity') || $can('notifications'))
            <p class="admin-nav__label">Administration</p>

            @if ($can('notifications'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-notifications')]) href="{{ route('admin.notifications.index') }}">
                    <i class="fa-solid fa-bell"></i>
                    <span>Notifications</span>
                    @if (($adminUnreadNotificationCount ?? 0) > 0)
                        <strong class="admin-nav__badge">
                            {{ $adminUnreadNotificationCount > 99 ? '99+' : $adminUnreadNotificationCount }}
                        </strong>
                    @endif
                </a>
            @endif

            @if ($can('activity'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-activity')]) href="{{ route('admin.activite.index') }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Activité</span>
                </a>
            @endif

            @if ($can('roles'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-roles')]) href="{{ route('admin.roles.index') }}">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Rôles</span>
                </a>
            @endif

            @if ($can('settings'))
                <a @class(['admin-nav__link', 'is-active' => str_starts_with($activeAdmin, 'admin-settings')]) href="{{ route('admin.parametres.index') }}">
                    <i class="fa-solid fa-gear"></i>
                    <span>Paramètres</span>
                </a>
            @endif
        @endif
    </nav>
</aside>

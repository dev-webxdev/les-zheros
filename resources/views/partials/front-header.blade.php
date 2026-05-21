@php($activePage = $activePage ?? '')
<header class="site-header">
    <div class="container">
        <div class="site-header-bar">
            <a class="site-logo" href="{{ route('accueil') }}"><img src="{{ asset('assets/img/logo.png') }}" alt=""></a>

            <button class="site-menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav-panel" aria-label="Ouvrir le menu" data-nav-toggle>
                <span class="site-menu-toggle-line"></span>
                <span class="site-menu-toggle-line"></span>
                <span class="site-menu-toggle-line"></span>
            </button>

            <div class="site-nav-panel" id="site-nav-panel" data-nav>
                <div class="site-nav-panel-shell">
                    <nav class="site-nav site-nav--main" aria-label="Navigation principale">
                        <ul>
                            <li @class(['is-active' => $activePage === 'index'])><a href="{{ route('accueil') }}"><i class="fa-solid fa-house"></i><span>Accueil</span></a></li>
                            <li @class(['is-active' => $activePage === 'sorties'])><a href="{{ route('sorties.index') }}"><i class="fa-solid fa-users"></i><span>Sorties</span></a></li>
                            <li @class(['is-active' => $activePage === 'guides' || $activePage === 'guide'])><a href="{{ route('guides.index') }}"><i class="fa-solid fa-book-open"></i><span>Guides</span></a></li>
                            <li @class(['is-active' => $activePage === 'galerie'])><a href="{{ route('galerie') }}"><i class="fa-regular fa-images"></i><span>Galerie</span></a></li>
                            <li @class(['is-active' => $activePage === 'classement'])><a href="{{ route('classement') }}"><i class="fa-solid fa-trophy"></i><span>Classement</span></a></li>
                            <li @class(['is-active' => $activePage === 'missions'])><a href="{{ route('missions.index') }}"><i class="fa-solid fa-scroll"></i><span>Missions</span></a></li>
                            <li @class(['is-active' => $activePage === 'stuffs'])><a href="{{ route('stuffs.index') }}"><i class="fa-solid fa-shield-halved"></i><span>Stuffs</span></a></li>
                        </ul>
                    </nav>
                    <nav class="site-nav site-nav--account" aria-label="Navigation du compte">
                        <ul>
                            @auth
                                <li @class(['is-active' => $activePage === 'profil'])><a href="{{ route('profil') }}"><i class="fa-solid fa-user"></i><span>{{ auth()->user()->name }}</span></a></li>
                                @if (auth()->user()->hasAdminAccess())
                                    <li class="account-action account-action--admin"><a href="{{ route('admin.dashboard') }}" aria-label="Administration du site" title="Administration du site"><i class="fa-solid fa-user-shield"></i></a></li>
                                @endif
                                <li class="account-action account-action--logout">
                                    <form action="{{ route('deconnexion') }}" method="post">
                                        @csrf
                                        <button type="submit" aria-label="Deconnexion" title="Deconnexion"><i class="fa-solid fa-right-from-bracket"></i></button>
                                    </form>
                                </li>
                            @else
                                <li @class(['is-active' => $activePage === 'connexion'])><a href="{{ route('connexion') }}"><i class="fa-solid fa-right-to-bracket"></i><span>Connexion</span></a></li>
                            @endauth
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</header>

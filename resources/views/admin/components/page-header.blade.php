<header class="admin-topbar">
    <div class="admin-breadcrumb">
        <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
            <i class="fa-solid fa-table-columns"></i>
        </button>
        <span></span>
        <p>{{ $breadcrumb }}</p>
    </div>

    @isset($actions)
        <div class="admin-actions">
            {{ $actions }}
        </div>
    @endisset
</header>

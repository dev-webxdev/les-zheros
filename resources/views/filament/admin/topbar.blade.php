@php
    $segments = collect(request()->segments());
    $section = $segments->contains('announcements') ? 'Annonces' : 'Dashboard';
@endphp

<header class="lz-filament-topbar">
    <div class="lz-filament-breadcrumb">
        <button class="lz-filament-menu-button" type="button" aria-label="Menu">
            <svg viewBox="0 0 20 20" aria-hidden="true">
                <path d="M4 6.5h12M4 10h12M4 13.5h12" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            </svg>
        </button>
        <span></span>
        <p>{{ $section }}</p>
    </div>
</header>

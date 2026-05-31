@php
    $contentClass = trim('admin-content '.($contentClass ?? ''));
    $canEmptyTrash = $canEmptyTrash ?? false;
    $emptyButtonFirst = $emptyButtonFirst ?? false;
    $titleText = $titleText ?? 'Corbeille';
@endphp

<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>{{ $breadcrumb }}</p>
        </div>

        <div class="admin-actions">
            @if($emptyButtonFirst && $canEmptyTrash && $items->isNotEmpty())
                <form action="{{ $emptyTrashUrl }}" method="post" data-real-form>
                    @csrf
                    @method('delete')
                    <button class="admin-danger-button" type="submit">
                        <i class="fa-regular fa-trash-can"></i>
                        <span>Vider la corbeille</span>
                    </button>
                </form>
            @endif
            <a class="admin-secondary-button" href="{{ $backUrl }}">
                <i class="fa-solid fa-arrow-left"></i>
                <span>{{ $backLabel }}</span>
            </a>
            @if(! $emptyButtonFirst && $canEmptyTrash && $items->isNotEmpty())
                <form action="{{ $emptyTrashUrl }}" method="post" data-real-form>
                    @csrf
                    @method('delete')
                    <button class="admin-danger-button" type="submit">
                        <i class="fa-regular fa-trash-can"></i>
                        <span>Vider la corbeille</span>
                    </button>
                </form>
            @endif
        </div>
    </header>

    <section class="{{ $contentClass }}">
        <div class="admin-title">
            <i class="{{ $titleIcon }}"></i>
            <h1>{{ $titleText }}</h1>
        </div>

        @isset($bulk)
            @include('admin.partials.bulk-actions', $bulk)
        @endisset

        @include($tableView)
        @include('partials.admin-pagination', ['paginator' => $items])
    </section>
</main>

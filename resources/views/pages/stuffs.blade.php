@php
    use App\Models\Stuff;

    $bodyClass = 'home-page';
    $activePage = 'stuffs';
    $stuffClasses = collect(Stuff::CLASSES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values();
    $stuffLevels = Stuff::LEVELS;
    $stuffElements = [
        ['value' => 'feu', 'label' => 'Feu', 'icon' => 'icon-fire.avif', 'filter' => 'element'],
        ['value' => 'eau', 'label' => 'Eau', 'icon' => 'icon-water.avif', 'filter' => 'element'],
        ['value' => 'air', 'label' => 'Air', 'icon' => 'icon-air.avif', 'filter' => 'element'],
        ['value' => 'terre', 'label' => 'Terre', 'icon' => 'icon-earth.avif', 'filter' => 'element'],
        ['value' => 'multi', 'label' => 'Multi', 'icon' => 'icon-multi.avif', 'filter' => 'element'],
        ['value' => 'tank', 'label' => 'Tank', 'icon' => 'icon-tank.avif', 'filter' => 'mode'],
        ['value' => 'prospection', 'label' => 'Prospection', 'icon' => 'icon-prospecting.avif', 'filter' => 'element'],
        ['value' => 'do-pou', 'label' => 'Do pou', 'icon' => 'icon-do-pou.avif', 'filter' => 'element'],
    ];
    $stuffModes = [
        ['value' => 'dps', 'label' => 'DPS'],
        ['value' => 'tank', 'label' => 'Tank'],
        ['value' => 'soutien', 'label' => 'Soutien'],
    ];
@endphp

@extends('layouts.front')

@section('title', 'Stuffs | Les Z-héros')
@section('description', 'Catalogue des stuffs proposés par la guilde Les Z-héros.')

@section('content')
<section class="page-hero stuff-hero">
    <div class="container page-hero__content">
        <span class="section-kicker">Catalogue tactique</span>
        <h1>Stuffs de <span>guilde</span></h1>
        <p>Compare les builds validés par l'administration, filtre par classe ou rôle, puis ouvre le détail complet sur Dofusbook.</p>
    </div>
</section>

<section class="stuff-catalog" data-stuff-catalog>
    <div class="container">
        <form class="stuff-filters" aria-label="Filtres du catalogue de stuffs">
            <input type="hidden" value="" data-stuff-filter="class">
            <input type="hidden" value="" data-stuff-filter="element">
            <input type="hidden" value="" data-stuff-filter="mode">

            <div class="stuff-filter-panel">
                <div class="stuff-filter-panel__head">
                    <span>Filtres</span>
                    <button class="stuff-filter-reset" type="reset" data-stuff-reset aria-label="Réinitialiser les filtres" title="Réinitialiser">
                        <i class="fa-solid fa-sliders"></i>
                    </button>
                </div>

                <section class="stuff-filter-group" aria-labelledby="stuff-filter-classes-title">
                    <h2 id="stuff-filter-classes-title">Classes</h2>
                    <div class="stuff-class-filter-grid">
                        <button class="stuff-class-filter is-active" type="button" data-stuff-pick="class" data-stuff-value="" aria-label="Toutes les classes" aria-pressed="true">
                            <i class="fa-solid fa-border-all"></i>
                        </button>
                        @foreach($stuffClasses as $class)
                            <button class="stuff-class-filter" type="button" data-stuff-pick="class" data-stuff-value="{{ $class['value'] }}" aria-label="{{ $class['label'] }}" aria-pressed="false">
                                <img src="{{ asset('assets/img/classes/avatar-'.$class['value'].'.avif') }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="stuff-filter-group" aria-labelledby="stuff-filter-elements-title">
                    <h2 id="stuff-filter-elements-title">Éléments</h2>
                    <div class="stuff-tag-filter-grid">
                        @foreach($stuffElements as $element)
                            <button class="stuff-tag-filter stuff-tag-filter--{{ $element['value'] }}" type="button" data-stuff-pick="{{ $element['filter'] }}" data-stuff-value="{{ $element['value'] }}" data-stuff-tooltip="{{ $element['label'] }}" aria-label="{{ $element['label'] }}" aria-pressed="false">
                                <img src="{{ asset('assets/img/classes/'.$element['icon']) }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="stuff-filter-panel stuff-filter-panel--compact">
                <section class="stuff-filter-group" aria-labelledby="stuff-filter-level-title">
                    <h2 id="stuff-filter-level-title">Niveau</h2>
                    <label class="stuff-filter-select" for="stuff-filter-level-visual">
                        <select id="stuff-filter-level-visual" data-stuff-filter="level">
                            <option value="">Tous</option>
                            @foreach($stuffLevels as $level)
                                <option value="{{ $level }}" @selected($level === 200)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </label>
                </section>

                <section class="stuff-filter-group" aria-labelledby="stuff-filter-mode-title">
                    <h2 id="stuff-filter-mode-title">Rôle</h2>
                    <div class="stuff-mode-filter-list">
                        <button class="stuff-mode-filter is-active" type="button" data-stuff-pick="mode" data-stuff-value="" aria-pressed="true">Tous</button>
                        @foreach($stuffModes as $mode)
                            <button class="stuff-mode-filter" type="button" data-stuff-pick="mode" data-stuff-value="{{ $mode['value'] }}" aria-pressed="false">{{ $mode['label'] }}</button>
                        @endforeach
                    </div>
                </section>
            </div>
        </form>

        <div class="stuff-catalog-head">
            <div>
                <span class="section-kicker">Builds disponibles</span>
                <h2>Propositions récentes</h2>
            </div>
            <p><strong data-stuff-count>{{ $stuffs->count() }}</strong> builds affichés</p>
        </div>

        <div class="stuff-grid" data-stuff-grid>
            @foreach($stuffs as $stuff)
                <a @class(['stuff-build-card', 'stuff-build-card--featured' => $stuff->is_featured]) href="{{ $stuff->dofusbook_url }}" target="_blank" rel="noopener" data-stuff-card data-class="{{ $stuff->class_slug }}" data-element="{{ $stuff->elementsText(' ') }}" data-mode="{{ \Illuminate\Support\Str::slug($stuff->mode) }}" data-level-min="{{ $stuff->min_level }}" data-level-max="{{ $stuff->max_level }}">
                    <div class="stuff-build-card__scene stuff-build-card__scene--{{ $stuff->class_slug }}">
                        <img src="{{ asset('assets/img/classes/'.$stuff->class_slug.'.png') }}" alt="{{ $stuff->class_label }}" loading="lazy">
                        <span class="stuff-build-card__level">{{ $stuff->levelLabel() }}</span>
                    </div>
                    <div class="stuff-build-card__body">
                        <div class="stuff-build-card__title-row">
                            <div>
                                <span>{{ $stuff->class_label }}</span>
                                <h2>{{ $stuff->title }}</h2>
                            </div>
                            <strong>{{ $stuff->mode }}</strong>
                        </div>
                        <p>{{ $stuff->description ?: 'Aucune note ajoutée pour ce build.' }}</p>
                        <div class="stuff-build-card__chips">
                            @foreach($stuff->chips() as $chip)
                                <span>{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <p class="stuff-empty" data-stuff-empty data-empty-filtered="Aucun build ne correspond aux filtres sélectionnés." data-empty-default="Aucun stuff publié pour le moment." hidden>Aucun build ne correspond aux filtres sélectionnés.</p>
    </div>
</section>
@endsection

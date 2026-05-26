@php($shortcuts = $this->shortcuts())

<x-filament-panels::page>
    <div class="lz-dashboard lz-dashboard--simple">
        <section class="lz-dashboard-simple-head">
            <div>
                <h1>Dashboard</h1>
            </div>
            <p>Ton espace de travail admin, sans doubler les pages de gestion.</p>
        </section>

        <section class="lz-dashboard-simple-grid">
            <article class="lz-dashboard-panel lz-dashboard-note" x-data="{ note: localStorage.getItem('les-zheros-filament-dashboard-note') || '', saved: false }">
                <div class="lz-dashboard-panel__head">
                    <div>
                        <h2>Notes admin</h2>
                        <p>Un bloc simple pour les rappels d'equipe.</p>
                    </div>
                </div>

                <textarea
                    x-model="note"
                    rows="7"
                    placeholder="Ex: penser a publier la galerie apres la sortie de vendredi."
                ></textarea>

                <div class="lz-dashboard-note__actions">
                    <button
                        type="button"
                        x-on:click="localStorage.setItem('les-zheros-filament-dashboard-note', note); saved = true; setTimeout(() => saved = false, 1800)"
                    >
                        Enregistrer
                    </button>
                    <span x-show="saved" x-cloak>Notes enregistrees</span>
                </div>
            </article>

            <article class="lz-dashboard-panel lz-dashboard-shortcuts-panel">
                <div class="lz-dashboard-panel__head">
                    <div>
                        <h2>Raccourcis</h2>
                        <p>Les creations qu'on ouvre souvent.</p>
                    </div>
                </div>

                @if ($shortcuts !== [])
                    <div class="lz-dashboard-shortcuts">
                        @foreach ($shortcuts as $shortcut)
                            <a href="{{ $shortcut['url'] }}">
                                <span>{{ mb_substr($shortcut['label'], 0, 1) }}</span>
                                {{ $shortcut['label'] }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="lz-dashboard-muted">Aucun raccourci disponible avec tes permissions.</p>
                @endif
            </article>
        </section>
    </div>
</x-filament-panels::page>

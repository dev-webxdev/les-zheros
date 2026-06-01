@extends('layouts.admin')

@section('title', 'Paramètres | Les Z-héros')
@section('description', 'Paramètres de l\'administration Les Z-héros.')
@php($activeAdmin = 'admin-settings')
@php($settings = $settings ?? \App\Models\GuildSetting::values())
@php($backups = collect($backups ?? []))
@php($backupCoverage = $backupCoverage ?? app(\App\Support\SiteBackupManager::class)->coverage())
@php($canSetting = fn (string $setting): bool => (bool) auth()->user()?->canAccessAdminPermission('settings.'.$setting))
@php($settingsTabs = collect([
    'missions' => ['label' => 'Missions', 'icon' => 'fa-solid fa-sliders', 'visible' => $canSetting('cycle') || $canSetting('points')],
    'lottery' => ['label' => 'Loterie', 'icon' => 'fa-solid fa-dice', 'visible' => $canSetting('lottery')],
    'word-mystery' => ['label' => 'Mot Mystere', 'icon' => 'fa-solid fa-key', 'visible' => $canSetting('word_mystery')],
    'maintenance' => ['label' => 'Maintenance', 'icon' => 'fa-solid fa-screwdriver-wrench', 'visible' => $canSetting('maintenance')],
    'backups' => ['label' => 'Sauvegardes', 'icon' => 'fa-solid fa-box-archive', 'visible' => $canSetting('backups')],
])->filter(fn ($tab) => $tab['visible']))
@php($activeSettingsTab = $settingsTabs->keys()->first())
@push('scripts')
<script src="{{ asset('assets/js/admin-settings.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-breadcrumb">
                    <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                        <i class="fa-solid fa-table-columns"></i>
                    </button>
                    <span></span>
                    <p>Paramètres</p>
                </div>
            </header>

            <section class="admin-content admin-settings" data-admin-settings>
                <div class="admin-title admin-title--split">
                    <div>
                        <i class="fa-solid fa-gear"></i>
                        <h1>Paramètres</h1>
                    </div>
                    <p>Réglages globaux du cycle des missions et du calcul des points.</p>
                </div>

                <nav class="admin-settings-tabs" aria-label="Onglets des parametres">
                    @foreach($settingsTabs as $tabKey => $tab)
                        <button @class(['is-active' => $activeSettingsTab === $tabKey]) type="button" data-settings-tab="{{ $tabKey }}" aria-pressed="{{ $activeSettingsTab === $tabKey ? 'true' : 'false' }}"><i class="{{ $tab['icon'] }}"></i><span>{{ $tab['label'] }}</span></button>
                    @endforeach
                </nav>

                <section @class(['admin-settings-panel', 'is-active' => $activeSettingsTab === 'missions']) data-settings-panel="missions" @if($activeSettingsTab !== 'missions') hidden @endif>
                <div class="admin-settings-grid">
                    @if ($canSetting('cycle'))
                    <article class="admin-settings-card">
                        <header class="admin-settings-card__head">
                            <span class="admin-settings-card__icon"><i class="fa-solid fa-hourglass-half"></i></span>
                            <div>
                                <h2>Date de fin des missions</h2>
                                <p>Définis la fin du cycle actif. Une fois dépassée, la date sera prolongée de 7 jours.</p>
                            </div>
                        </header>

                        <form class="admin-settings-form" action="{{ route('admin.parametres.cycle.update') }}" method="post" data-settings-form="missions-cycle" data-real-form>
                            @csrf
                            @method('patch')
                            <label class="admin-field" for="mission-cycle-end">
                                <span>Date de fin</span>
                                <input id="mission-cycle-end" name="mission_cycle_end" type="datetime-local" value="{{ old('mission_cycle_end', $settings[\App\Models\GuildSetting::MISSION_CYCLE_END]) }}" data-cycle-end>
                            </label>

                            <p class="admin-settings-help" data-cycle-summary>
                                Les missions restent déclarables jusqu'au mardi 19 mai 2026 à 08:00.
                            </p>

                            <button class="admin-create-button admin-settings-submit" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Enregistrer</span>
                            </button>
                        </form>
                    </article>
                    @endif

                    @if ($canSetting('points'))
                    <article class="admin-settings-card">
                        <header class="admin-settings-card__head">
                            <span class="admin-settings-card__icon"><i class="fa-solid fa-sliders"></i></span>
                            <div>
                                <h2>Barème de points</h2>
                                <p>Règle les points qui servent ensuite à calculer les tickets de loterie.</p>
                            </div>
                        </header>

                        <form class="admin-settings-form" action="{{ route('admin.parametres.points.update') }}" method="post" data-settings-form="points" data-real-form>
                            @csrf
                            @method('patch')
                            <div class="admin-settings-field-grid">
                                <label class="admin-field" for="mission-points-base">
                                    <span>Mission terminée</span>
                                    <input id="mission-points-base" name="mission_points_base" type="number" min="0" step="0.01" value="{{ old('mission_points_base', number_format((float) $settings[\App\Models\GuildSetting::MISSION_POINTS_BASE], 2, '.', '')) }}" data-points-base>
                                </label>

                                <label class="admin-field" for="mission-points-bonus">
                                    <span>Bonus par perso</span>
                                    <input id="mission-points-bonus" name="mission_bonus_per_extra_character" type="number" min="0" step="0.01" value="{{ old('mission_bonus_per_extra_character', number_format((float) $settings[\App\Models\GuildSetting::MISSION_BONUS_PER_EXTRA_CHARACTER], 2, '.', '')) }}" data-points-bonus>
                                </label>

                                <label class="admin-field" for="guild-help-points">
                                    <span>Aide guilde</span>
                                    <input id="guild-help-points" name="guild_help_points" type="number" min="0" step="0.01" value="{{ old('guild_help_points', number_format((float) $settings[\App\Models\GuildSetting::GUILD_HELP_POINTS], 2, '.', '')) }}" data-help-points>
                                </label>
                            </div>

                            <div class="admin-settings-preview" data-points-preview>
                                Mission à 4 personnages : 1,75 pts. Aide guilde : 0,50 pt.
                            </div>

                            <button class="admin-create-button admin-settings-submit" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Sauvegarder le barème</span>
                            </button>
                        </form>
                    </article>
                    @endif

                </div>
                </section>

                <section @class(['admin-settings-panel', 'is-active' => $activeSettingsTab === 'lottery']) data-settings-panel="lottery" @if($activeSettingsTab !== 'lottery') hidden @endif>
                <div class="admin-settings-grid">
                    @if ($canSetting('lottery'))
                    <article class="admin-settings-card">
                        <header class="admin-settings-card__head">
                            <span class="admin-settings-card__icon"><i class="fa-solid fa-dice"></i></span>
                            <div>
                                <h2>Loterie</h2>
                                <p>Configure les gains, les tickets et le seuil minimum pour les tirages.</p>
                            </div>
                        </header>

                        <form class="admin-settings-form" action="{{ route('admin.parametres.lottery.update') }}" method="post" data-real-form>
                            @csrf
                            @method('patch')
                            <div class="admin-settings-field-grid">
                                <label class="admin-field" for="lottery-prize-1">
                                    <span>Gain 1er</span>
                                    <input id="lottery-prize-1" name="lottery_prize_1" type="number" min="0" step="1000" value="{{ old('lottery_prize_1', (int) $settings[\App\Models\GuildSetting::LOTTERY_PRIZE_1]) }}">
                                </label>

                                <label class="admin-field" for="lottery-prize-2">
                                    <span>Gain 2e</span>
                                    <input id="lottery-prize-2" name="lottery_prize_2" type="number" min="0" step="1000" value="{{ old('lottery_prize_2', (int) $settings[\App\Models\GuildSetting::LOTTERY_PRIZE_2]) }}">
                                </label>

                                <label class="admin-field" for="lottery-prize-3">
                                    <span>Gain 3e</span>
                                    <input id="lottery-prize-3" name="lottery_prize_3" type="number" min="0" step="1000" value="{{ old('lottery_prize_3', (int) $settings[\App\Models\GuildSetting::LOTTERY_PRIZE_3]) }}">
                                </label>

                                <label class="admin-field" for="lottery-min-points">
                                    <span>Points mini</span>
                                    <input id="lottery-min-points" name="lottery_min_points" type="number" min="0" step="0.25" value="{{ old('lottery_min_points', number_format((float) $settings[\App\Models\GuildSetting::LOTTERY_MIN_POINTS], 2, '.', '')) }}">
                                </label>
                            </div>

                            <button class="admin-create-button admin-settings-submit" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Sauvegarder la loterie</span>
                            </button>
                        </form>
                    </article>
                    @endif

                </div>
                </section>

                <section @class(['admin-settings-panel', 'is-active' => $activeSettingsTab === 'word-mystery']) data-settings-panel="word-mystery" @if($activeSettingsTab !== 'word-mystery') hidden @endif>
                <div class="admin-settings-grid">
                    <article class="admin-settings-card admin-settings-card--wide">
                        <header class="admin-settings-card__head">
                            <span class="admin-settings-card__icon"><i class="fa-solid fa-key"></i></span>
                            <div>
                                <h2>Mot Mystere</h2>
                                <p>Configure un gain de base par difficulte, puis un bonus en pourcentage selon le nombre d essais.</p>
                            </div>
                        </header>

                        <form class="admin-settings-form" action="{{ route('admin.parametres.word-mystery.update') }}" method="post" data-real-form>
                            @csrf
                            @method('patch')
                            <div class="admin-table-card admin-word-week-card">
                                <table class="admin-table admin-table--word-rewards">
                                    <thead>
                                        <tr>
                                            <th>Difficulte</th>
                                            <th>Gain de base</th>
                                            @foreach(range(1, \App\Services\WordMysteryService::MAX_ATTEMPTS) as $attempt)
                                                <th>Bonus {{ $attempt }} essai{{ $attempt > 1 ? 's' : '' }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(\App\Models\WordMysteryWord::DIFFICULTIES as $difficultyKey => $difficultyLabel)
                                            <tr>
                                                <td><strong>{{ $difficultyLabel }}</strong></td>
                                                <td>
                                                    <label class="admin-word-mystery-reward-field">
                                                        <input class="admin-word-mystery-reward-input" name="word_mystery_rewards[{{ $difficultyKey }}][base]" type="text" inputmode="numeric" value="{{ old("word_mystery_rewards.$difficultyKey.base", number_format((int) ($settings[\App\Models\GuildSetting::WORD_MYSTERY_REWARDS][$difficultyKey]['base'] ?? 0), 0, ',', ' ')) }}" required>
                                                        <span>kamas</span>
                                                    </label>
                                                </td>
                                                @foreach(range(1, \App\Services\WordMysteryService::MAX_ATTEMPTS) as $attempt)
                                                    <td>
                                                        <label class="admin-word-mystery-reward-field">
                                                            <input class="admin-word-mystery-reward-input" name="word_mystery_rewards[{{ $difficultyKey }}][bonuses][{{ $attempt }}]" type="number" min="-100" max="500" step="1" value="{{ old("word_mystery_rewards.$difficultyKey.bonuses.$attempt", (int) ($settings[\App\Models\GuildSetting::WORD_MYSTERY_REWARDS][$difficultyKey]['bonuses'][$attempt] ?? 0)) }}" required>
                                                            <span>%</span>
                                                        </label>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <button class="admin-create-button admin-settings-submit" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Sauvegarder Mot Mystere</span>
                            </button>
                        </form>
                    </article>

                </div>
                </section>

                @if($canSetting('maintenance'))
                <section @class(['admin-settings-panel', 'is-active' => $activeSettingsTab === 'maintenance']) data-settings-panel="maintenance" @if($activeSettingsTab !== 'maintenance') hidden @endif>
                <div class="admin-settings-grid">
                    <article class="admin-settings-card">
                        <header class="admin-settings-card__head">
                            <span class="admin-settings-card__icon"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                            <div>
                                <h2>Maintenance</h2>
                                <p>Coupe temporairement le front avec une page propre. Les admins peuvent toujours entrer.</p>
                            </div>
                        </header>

                        <form class="admin-settings-form" action="{{ route('admin.parametres.maintenance.update') }}" method="post" data-real-form>
                            @csrf
                            @method('patch')
                            <label class="admin-toggle-field admin-settings-toggle">
                                <input type="checkbox" name="maintenance_enabled" value="1" @checked(old('maintenance_enabled', $settings[\App\Models\GuildSetting::MAINTENANCE_ENABLED]))>
                                <span>Activer la maintenance</span>
                            </label>

                            <label class="admin-field" for="maintenance-message">
                                <span>Message affiché</span>
                                <input id="maintenance-message" name="maintenance_message" type="text" value="{{ old('maintenance_message', $settings[\App\Models\GuildSetting::MAINTENANCE_MESSAGE]) }}">
                            </label>

                            <button class="admin-create-button admin-settings-submit" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Sauvegarder la maintenance</span>
                            </button>
                        </form>
                    </article>

                </div>
                </section>
                @endif

                @if($canSetting('backups'))
                <section @class(['admin-settings-panel', 'is-active' => $activeSettingsTab === 'backups']) data-settings-panel="backups" @if($activeSettingsTab !== 'backups') hidden @endif>
                <div class="admin-settings-grid">
                    <article class="admin-settings-card admin-settings-card--wide">
                        <header class="admin-settings-card__head">
                            <span class="admin-settings-card__icon"><i class="fa-solid fa-box-archive"></i></span>
                            <div>
                                <h2>Sauvegardes du site</h2>
                                <p>Crée, télécharge ou restaure une archive contenant la base SQLite et les fichiers uploadés.</p>
                            </div>
                        </header>

                        <form class="admin-settings-form" action="{{ route('admin.parametres.backups.store') }}" method="post" data-real-form data-backup-action="create">
                            @csrf
                            <button class="admin-create-button admin-settings-submit" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Créer une sauvegarde</span>
                            </button>
                        </form>

                        <div class="admin-backup-coverage" aria-label="Contenu des sauvegardes">
                            <div class="admin-backup-coverage__head">
                                <strong>Sauvegarde automatique</strong>
                                <span>{{ $backupCoverage['automatic'] }} Conservation : {{ $backupCoverage['keep'] }} archives.</span>
                            </div>
                            <div class="admin-backup-coverage__grid">
                                @foreach($backupCoverage['includes'] as $included)
                                    <article>
                                        <i class="fa-solid fa-circle-check"></i>
                                        <div>
                                            <strong>{{ $included['label'] }}</strong>
                                            <span>{{ $included['detail'] }}</span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        <div class="admin-backup-list">
                            @forelse ($backups as $backup)
                                <article class="admin-backup-row">
                                    <div>
                                        <strong>{{ $backup['display_name'] ?? $backup['name'] }}</strong>
                                        <span>{{ $backup['type_label'] ?? 'Sauvegarde' }} · {{ \Illuminate\Support\Number::fileSize($backup['size']) }}</span>
                                    </div>

                                    <div class="admin-backup-actions">
                                        <a class="admin-secondary-button admin-backup-button admin-backup-button--download" href="{{ route('admin.parametres.backups.download', $backup['name']) }}">
                                            <i class="fa-solid fa-download"></i>
                                            <span>Télécharger</span>
                                        </a>

                                        <form action="{{ route('admin.parametres.backups.restore', $backup['name']) }}" method="post" data-real-form data-backup-action="restore" data-confirm-form data-confirm-variant="warning" data-confirm-icon="restore" data-confirm-title="Restaurer cette sauvegarde ?" data-confirm-text="Le site sera remis dans l’état de cette sauvegarde. Une sauvegarde de sécurité sera créée avant restauration si nécessaire." data-confirm-submit="Restaurer">
                                            @csrf
                                            <input name="confirmation" type="text" placeholder="RESTAURER" required pattern="RESTAURER" aria-label="Confirmation de restauration">
                                            <button class="admin-backup-button admin-backup-button--restore" type="submit">
                                                <i class="fa-solid fa-clock-rotate-left"></i>
                                                <span>Restaurer</span>
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.parametres.backups.destroy', $backup['name']) }}" method="post" data-real-form data-confirm-title="Supprimer cette sauvegarde ?" data-confirm-text="Cette archive sera supprimée définitivement." data-confirm-submit="Supprimer">
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete admin-backup-delete" type="submit" aria-label="Supprimer {{ $backup['name'] }}" title="Supprimer">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @empty
                                <p class="admin-settings-help">Aucune sauvegarde créée pour le moment.</p>
                            @endforelse
                        </div>
                    </article>
                </div>
                </section>
                @endif

            </section>
        </main>
@endsection

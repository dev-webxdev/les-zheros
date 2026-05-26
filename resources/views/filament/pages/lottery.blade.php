<x-filament-panels::page>
    @php
        $cycle = $this->selectedCycle();
        $participants = $this->participants();
        $settings = $this->lotterySettings();
        $latestDraw = $this->latestDraw();
        $currentWeekDraw = $this->currentWeekDraw();
        $drawHistory = $this->drawHistory();
    @endphp

    <div class="lz-lottery" x-data="lzLotteryDrawModal()" x-on:lottery-drawn.window="open($event.detail.draw)">
        <div class="lz-lottery__toolbar">
            <label class="lz-lottery__week">
                <span>Semaine a tirer</span>
                <select wire:model.live="selectedWeek">
                    @foreach ($this->weeks() as $week)
                        <option value="{{ $week['value'] }}">{{ $week['label'] }}</option>
                    @endforeach
                </select>
            </label>

            <span @class([
                'lz-lottery__status',
                'lz-lottery__status--done' => filled($currentWeekDraw),
            ])>
                {{ filled($currentWeekDraw) ? 'Tirage effectue' : 'Tirage disponible' }}
            </span>
        </div>

        <section class="lz-lottery-panel">
            <div class="lz-lottery-panel__head">
                <div>
                    <h2>Parametres du tirage</h2>
                    <p>{{ $cycle['label'] ?? 'Cycle en cours' }}</p>
                </div>
            </div>

            <div class="lz-lottery-stats">
                <article>
                    <span>Participants eligibles</span>
                    <strong>{{ count($participants) }}</strong>
                </article>
                <article>
                    <span>Total tickets</span>
                    <strong>{{ $this->totalTickets() }}</strong>
                </article>
                <article>
                    <span>Dernier tirage</span>
                    <strong>{{ $latestDraw?->drawn_at?->translatedFormat('d/m/Y H:i') ?? 'Aucun tirage' }}</strong>
                </article>
            </div>

            @if ($latestDraw)
                <div class="lz-lottery-result">
                    <div>
                        <span>Resultat du tirage</span>
                        <strong>{{ $latestDraw->cycle_label }}</strong>
                    </div>
                    <ol>
                        @foreach ($latestDraw->winners ?? [] as $winner)
                            <li>
                                <span>#{{ $loop->iteration }}</span>
                                <strong>{{ $winner['name'] }}</strong>
                                <em>{{ $this->formatKamas($winner['prize']) }}</em>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </section>

        <section class="lz-lottery-panel">
            <div class="lz-lottery-panel__head">
                <div>
                    <h2>Participants de la semaine</h2>
                    <p>Les tickets sont calcules avec les points valides et le multiplicateur du bareme.</p>
                </div>
                <span>Seuil : {{ $this->formatPoints($settings['min_points']) }} point(s)</span>
            </div>

            <div class="lz-lottery-table-wrap">
                <table class="lz-lottery-table">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Joueur</th>
                            <th>Points valides</th>
                            <th>Tickets</th>
                            <th>Missions</th>
                            <th>Aides</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($participants as $participant)
                            <tr>
                                <td>
                                    <span @class([
                                        'lz-lottery-rank',
                                        'lz-lottery-rank--gold' => $loop->iteration === 1,
                                        'lz-lottery-rank--silver' => $loop->iteration === 2,
                                        'lz-lottery-rank--bronze' => $loop->iteration === 3,
                                    ])>
                                        #{{ $loop->iteration }}
                                    </span>
                                </td>
                                <td>
                                    <div class="lz-lottery-user">
                                        <span class="lz-lottery-avatar">
                                            @if ($participant['avatar'])
                                                <img src="{{ $participant['avatar'] }}" alt="Avatar {{ $participant['name'] }}">
                                            @else
                                                {{ $participant['initials'] }}
                                            @endif
                                        </span>
                                        <strong>{{ $participant['name'] }}</strong>
                                    </div>
                                </td>
                                <td>{{ $this->formatPoints($participant['points']) }}</td>
                                <td><strong>{{ $participant['tickets'] }}</strong></td>
                                <td>{{ $participant['missions'] }}</td>
                                <td>{{ $participant['helps'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="lz-lottery-empty">
                                        <strong>Aucun participant eligible</strong>
                                        <span>Les joueurs avec des points valides apparaitront ici.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lz-lottery-panel">
            <div class="lz-lottery-panel__head">
                <div>
                    <h2>Historique des tirages</h2>
                    <p>Les derniers resultats sont sauvegardes pour verifier les gains distribues.</p>
                </div>
            </div>

            <div class="lz-lottery-table-wrap">
                <table class="lz-lottery-table lz-lottery-table--history">
                    <thead>
                        <tr>
                            <th>Date tirage</th>
                            <th>Semaine</th>
                            <th>#1</th>
                            <th>#2</th>
                            <th>#3</th>
                            <th>Kamas distribues</th>
                            <th>Tire par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($drawHistory as $draw)
                            <tr>
                                <td>{{ $draw->drawn_at?->translatedFormat('d/m/Y H:i') }}</td>
                                <td>{{ $draw->cycle_label }}</td>
                                @for ($index = 0; $index < 3; $index++)
                                    <td>
                                        @if (isset($draw->winners[$index]))
                                            <span class="lz-lottery-winner lz-lottery-winner--{{ $index + 1 }}">
                                                <small>#{{ $index + 1 }}</small>
                                                <strong>{{ $draw->winners[$index]['name'] }}</strong>
                                                <em>{{ $this->formatKamas($draw->winners[$index]['prize']) }}</em>
                                            </span>
                                        @else
                                            <span class="lz-lottery-muted">-</span>
                                        @endif
                                    </td>
                                @endfor
                                <td><strong>{{ $this->formatKamas($draw->total_prize) }}</strong></td>
                                <td>{{ $draw->drawn_by_name ?? $draw->drawer?->name ?? 'Admin' }}</td>
                                <td>
                                    <button class="lz-lottery-delete" type="button" wire:click="deleteDraw({{ $draw->id }})">
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="lz-lottery-empty">
                                        <strong>Aucun tirage enregistre.</strong>
                                        <span>Le prochain tirage hebdomadaire apparaitra ici.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="lz-lottery-draw-modal" x-cloak x-show="isOpen" x-transition.opacity>
            <div class="lz-lottery-draw-modal__backdrop"></div>

            <section class="lz-lottery-draw-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="lz-lottery-draw-title">
                <button class="lz-lottery-draw-modal__close" type="button" x-show="isDone" x-on:click="close()" aria-label="Fermer">
                    x
                </button>

                <span class="lz-lottery-draw-modal__eyebrow">Loterie hebdomadaire</span>
                <h2 id="lz-lottery-draw-title">Tirage de la loterie</h2>
                <p x-text="isDone ? 'Tirage termine' : 'Melange des tickets en cours...'"></p>

                <div class="lz-lottery-draw-slots">
                    <template x-for="(slot, index) in displayWinners" :key="index">
                        <article class="lz-lottery-draw-card" :class="isDone ? 'is-final lz-lottery-draw-card--' + (index + 1) : 'is-spinning'">
                            <span x-text="'#' + (index + 1)"></span>
                            <strong x-text="slot?.name || '...'"></strong>
                            <em x-text="isDone && slot?.prize ? formatKamas(slot.prize) : '...'"></em>
                        </article>
                    </template>
                </div>

                <div class="lz-lottery-draw-modal__actions" x-show="isDone">
                    <button class="lz-lottery-download" type="button" x-on:click="downloadImage()">
                        Telecharger l'image
                    </button>
                    <button class="lz-lottery-close" type="button" x-on:click="close()">Fermer</button>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('lzLotteryDrawModal', () => ({
                isOpen: false,
                isDone: false,
                draw: null,
                displayWinners: [{}, {}, {}],
                timer: null,

                open(draw) {
                    this.draw = draw;
                    this.isOpen = true;
                    this.isDone = false;
                    this.displayWinners = [{}, {}, {}];
                    this.spin();
                },

                close() {
                    if (!this.isDone) {
                        return;
                    }

                    this.isOpen = false;
                },

                spin() {
                    const participants = this.draw?.participants || [];
                    const winners = this.draw?.winners || [];
                    let ticks = 0;

                    window.clearInterval(this.timer);
                    this.timer = window.setInterval(() => {
                        ticks += 1;

                        this.displayWinners = [0, 1, 2].map(() => {
                            if (!participants.length) {
                                return {};
                            }

                            return participants[Math.floor(Math.random() * participants.length)];
                        });

                        if (ticks >= 34) {
                            window.clearInterval(this.timer);
                            this.reveal(winners);
                        }
                    }, 75);
                },

                reveal(winners) {
                    this.displayWinners = winners;
                    this.isDone = true;
                },

                formatKamas(value) {
                    return new Intl.NumberFormat('fr-FR').format(Number(value || 0)) + ' kamas';
                },

                downloadImage() {
                    if (!this.draw) {
                        return;
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = 1400;
                    canvas.height = 788;
                    const context = canvas.getContext('2d');

                    context.fillStyle = '#f6f8fc';
                    context.fillRect(0, 0, canvas.width, canvas.height);
                    this.roundRect(context, 40, 34, 1320, 720, 22);
                    context.fillStyle = '#ffffff';
                    context.fill();
                    context.strokeStyle = '#d8e1ee';
                    context.lineWidth = 2;
                    context.stroke();

                    context.fillStyle = '#4869ee';
                    context.font = '800 22px Inter, Arial';
                    context.fillText('LOTERIE HEBDOMADAIRE', 78, 92);
                    context.fillStyle = '#111827';
                    context.font = '800 52px Inter, Arial';
                    context.fillText('Resultats du tirage', 78, 150);
                    context.fillStyle = '#4b5563';
                    context.font = '700 30px Inter, Arial';
                    context.fillText(this.draw.week || 'Cycle en cours', 78, 202);

                    const themes = [
                        { bg: '#fff7d1', text: '#3a2500', muted: '#6d4f04', border: '#f0b90b' },
                        { bg: '#eef5ff', text: '#203a5f', muted: '#38547c', border: '#a9bedf' },
                        { bg: '#fff0e7', text: '#5b3020', muted: '#72422d', border: '#dd9c6b' },
                    ];

                    (this.draw.winners || []).forEach((winner, index) => {
                        const x = 78 + index * 420;
                        const y = 316;
                        const theme = themes[index] || themes[0];

                        this.roundRect(context, x, y, 380, 330, 18);
                        context.fillStyle = theme.bg;
                        context.fill();
                        context.strokeStyle = theme.border;
                        context.stroke();

                        context.fillStyle = theme.text;
                        context.font = '800 44px Inter, Arial';
                        context.fillText('#' + (index + 1), x + 36, y + 65);
                        context.font = '800 42px Inter, Arial';
                        context.fillText(winner.name || '...', x + 36, y + 148);
                        context.font = '800 30px Inter, Arial';
                        context.fillText(this.formatKamas(winner.prize), x + 36, y + 202);

                        context.fillStyle = theme.muted;
                        context.font = '800 24px Inter, Arial';
                        context.fillText(String(winner.points || 0).replace('.', ',') + ' pts', x + 36, y + 256);
                        context.fillText((winner.tickets || 0) + ' tickets', x + 36, y + 292);
                    });

                    const link = document.createElement('a');
                    link.download = 'resultats-loterie-' + Date.now() + '.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                },

                roundRect(context, x, y, width, height, radius) {
                    context.beginPath();
                    context.moveTo(x + radius, y);
                    context.lineTo(x + width - radius, y);
                    context.quadraticCurveTo(x + width, y, x + width, y + radius);
                    context.lineTo(x + width, y + height - radius);
                    context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
                    context.lineTo(x + radius, y + height);
                    context.quadraticCurveTo(x, y + height, x, y + height - radius);
                    context.lineTo(x, y + radius);
                    context.quadraticCurveTo(x, y, x + radius, y);
                    context.closePath();
                },
            }));
        });
    </script>
</x-filament-panels::page>

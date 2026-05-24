@php
    use App\Support\AdminAccess;
    use Filament\Support\Enums\VerticalAlignment;

    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $state = $getState();
    $roles = AdminAccess::roles();
    $initialRoles = is_array($state) && $state !== [] ? array_values($state) : [AdminAccess::MEMBER];
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :inline-label-vertical-alignment="VerticalAlignment::Start"
>
    <div
        class="lz-role-board"
        x-data="{
            roles: @js($initialRoles),
            labels: @js($roles),
            draggedRole: null,
            init() {
                this.roles = this.roles.filter((role, index, items) => this.labels[role] && items.indexOf(role) === index)

                if (! this.roles.length) {
                    this.roles = ['{{ AdminAccess::MEMBER }}']
                }

                this.sync()
            },
            availableRoles() {
                return Object.keys(this.labels).filter((role) => ! this.roles.includes(role))
            },
            selectedRoles() {
                return this.roles.filter((role) => this.labels[role])
            },
            startDrag(role) {
                this.draggedRole = role
            },
            stopDrag() {
                this.draggedRole = null
            },
            dropInto(target) {
                if (! this.draggedRole) {
                    return
                }

                target === 'selected' ? this.add(this.draggedRole) : this.remove(this.draggedRole)
                this.stopDrag()
            },
            add(role) {
                if (! this.labels[role] || this.roles.includes(role)) {
                    return
                }

                this.roles.push(role)
                this.sync()
            },
            remove(role) {
                this.roles = this.roles.filter((item) => item !== role)
                this.sync()
            },
            sync() {
                $wire.set(@js($statePath), this.roles, false)
            },
        }"
    >
        <section
            class="lz-role-board__column"
            x-on:dragover.prevent
            x-on:drop.prevent="dropInto('available')"
        >
            <div class="lz-role-board__head">
                <strong>Roles disponibles</strong>
                <span>A ajouter</span>
            </div>

            <div class="lz-role-board__list">
                <template x-for="role in availableRoles()" :key="role">
                    <button
                        class="lz-role-chip"
                        type="button"
                        draggable="true"
                        x-on:dragstart="startDrag(role)"
                        x-on:dragend="stopDrag()"
                        x-on:click="add(role)"
                    >
                        <span class="lz-role-chip__grip">::</span>
                        <span x-text="labels[role]"></span>
                    </button>
                </template>
            </div>
        </section>

        <section
            class="lz-role-board__column lz-role-board__column--selected"
            x-on:dragover.prevent
            x-on:drop.prevent="dropInto('selected')"
        >
            <div class="lz-role-board__head">
                <strong>Roles de l'utilisateur</strong>
                <span>Actifs</span>
            </div>

            <div class="lz-role-board__list">
                <p class="lz-role-board__empty" x-show="selectedRoles().length === 0">
                    Glisse les roles ici.
                </p>

                <template x-for="role in selectedRoles()" :key="role">
                    <button
                        class="lz-role-chip is-selected"
                        type="button"
                        draggable="true"
                        x-on:dragstart="startDrag(role)"
                        x-on:dragend="stopDrag()"
                        x-on:click="remove(role)"
                    >
                        <span class="lz-role-chip__grip">::</span>
                        <span x-text="labels[role]"></span>
                    </button>
                </template>
            </div>
        </section>
    </div>
</x-dynamic-component>

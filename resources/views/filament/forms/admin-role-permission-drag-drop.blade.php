@php
    use App\Support\AdminAccess;
    use Filament\Support\Enums\VerticalAlignment;

    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $state = $getState();
    $record = $field->getRecord();
    $isLocked = $record?->key === AdminAccess::ADMIN;
    $initialPermissions = is_array($state) ? array_values($state) : [];
    $permissionGroups = [
        'Contenus' => ['announcements.manage', 'announcements.delete', 'missions.manage', 'missions.delete', 'guides.manage', 'guides.delete', 'gallery.manage', 'gallery.delete', 'stuffs.manage', 'stuffs.delete'],
        'Activites' => ['outings.manage', 'outings.delete'],
        'Communaute' => ['users.manage', 'users.delete', 'validations.manage', 'validations.delete'],
        'Administration' => ['roles.manage', 'roles.delete'],
    ];
    $visiblePermissionKeys = collect($permissionGroups)->flatten()->all();
    $permissions = collect(AdminAccess::permissions())
        ->only($visiblePermissionKeys)
        ->all();
    $hiddenPermissions = array_values(array_diff($initialPermissions, array_keys($permissions)));
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :inline-label-vertical-alignment="VerticalAlignment::Start"
>
    @if ($isLocked)
        <div class="lz-admin-role-locked-note">
            <span class="lz-admin-role-locked-note__icon">
                <x-filament::icon icon="heroicon-o-lock-closed" />
            </span>
            <div>
                <strong>Acces administrateur verrouille</strong>
                <span>Le role Administrateur garde les droits de gestion du site, sauf maintenance et sauvegardes qui restent reservees au role Developpeur web.</span>
            </div>
        </div>
    @else
        <div
            class="lz-role-board lz-permission-board"
            x-data="{
            permissions: @js($initialPermissions),
            hiddenPermissions: @js($hiddenPermissions),
            labels: @js($permissions),
            groups: @js($permissionGroups),
            groupNames: @js(array_keys($permissionGroups)),
            locked: @js($isLocked),
            draggedPermission: null,
            init() {
                this.permissions = this.permissions.filter((permission, index, items) => this.labels[permission] && items.indexOf(permission) === index)
                this.sync()
            },
            availablePermissions() {
                return Object.keys(this.labels).filter((permission) => ! this.permissions.includes(permission))
            },
            selectedPermissions() {
                return this.permissions.filter((permission) => this.labels[permission])
            },
            groupPermissions(group, target) {
                const permissions = (this.groups[group] || []).filter((permission) => this.labels[permission])

                return target === 'selected'
                    ? permissions.filter((permission) => this.permissions.includes(permission))
                    : permissions.filter((permission) => ! this.permissions.includes(permission))
            },
            hasGroupPermissions(group, target) {
                return this.groupPermissions(group, target).length > 0
            },
            startDrag(permission) {
                if (this.locked) {
                    return
                }

                this.draggedPermission = permission
            },
            stopDrag() {
                this.draggedPermission = null
            },
            dropInto(target) {
                if (this.locked || ! this.draggedPermission) {
                    return
                }

                target === 'selected' ? this.add(this.draggedPermission) : this.remove(this.draggedPermission)
                this.stopDrag()
            },
            add(permission) {
                if (this.locked || ! this.labels[permission] || this.permissions.includes(permission)) {
                    return
                }

                this.permissions.push(permission)
                this.sync()
            },
            addGroup(group) {
                if (this.locked) {
                    return
                }

                this.groupPermissions(group, 'available').forEach((permission) => {
                    if (! this.permissions.includes(permission)) {
                        this.permissions.push(permission)
                    }
                })

                this.sync()
            },
            remove(permission) {
                if (this.locked) {
                    return
                }

                this.permissions = this.permissions.filter((item) => item !== permission)
                this.sync()
            },
            removeGroup(group) {
                if (this.locked) {
                    return
                }

                const permissions = this.groupPermissions(group, 'selected')
                this.permissions = this.permissions.filter((permission) => ! permissions.includes(permission))
                this.sync()
            },
            sync() {
                $wire.set(@js($statePath), [...new Set([...this.hiddenPermissions, ...this.permissions])], false)
            },
            }"
        >
            <section
                class="lz-role-board__column"
                x-on:dragover.prevent
                x-on:drop.prevent="dropInto('available')"
            >
                <div class="lz-role-board__head">
                    <strong>Permissions disponibles</strong>
                    <span>A ajouter</span>
                </div>

                <div class="lz-role-board__list lz-permission-board__list">
                    <template x-for="group in groupNames" :key="'available-' + group">
                        <div class="lz-permission-group" x-show="hasGroupPermissions(group, 'available')">
                            <div class="lz-permission-group__head">
                                <strong x-text="group"></strong>
                                <button type="button" x-on:click="addGroup(group)">Tout ajouter</button>
                            </div>

                            <template x-for="permission in groupPermissions(group, 'available')" :key="permission">
                                <button
                                    class="lz-role-chip"
                                    type="button"
                                    draggable="true"
                                    x-on:dragstart="startDrag(permission)"
                                    x-on:dragend="stopDrag()"
                                    x-on:click="add(permission)"
                                >
                                    <span class="lz-role-chip__grip">::</span>
                                    <span x-text="labels[permission]"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </section>

            <section
                class="lz-role-board__column lz-role-board__column--selected"
                x-on:dragover.prevent
                x-on:drop.prevent="dropInto('selected')"
            >
                <div class="lz-role-board__head">
                    <strong>Permissions du role</strong>
                    <span>Actifs</span>
                </div>

                <div class="lz-role-board__list lz-permission-board__list">
                    <p class="lz-role-board__empty" x-show="selectedPermissions().length === 0">
                        Glisse les droits ici.
                    </p>

                    <template x-for="group in groupNames" :key="'selected-' + group">
                        <div class="lz-permission-group" x-show="hasGroupPermissions(group, 'selected')">
                            <div class="lz-permission-group__head">
                                <strong x-text="group"></strong>
                                <button type="button" x-on:click="removeGroup(group)">Tout retirer</button>
                            </div>

                            <template x-for="permission in groupPermissions(group, 'selected')" :key="permission">
                                <button
                                    class="lz-role-chip is-selected"
                                    type="button"
                                    draggable="true"
                                    x-on:dragstart="startDrag(permission)"
                                    x-on:dragend="stopDrag()"
                                    x-on:click="remove(permission)"
                                >
                                    <span class="lz-role-chip__grip">::</span>
                                    <span x-text="labels[permission]"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </section>
        </div>
    @endif
</x-dynamic-component>

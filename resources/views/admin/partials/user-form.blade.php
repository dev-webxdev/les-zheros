@php
    $user = $user ?? new \App\Models\User();
    $isEdit = $user->exists;
    $formAction = $isEdit ? route('admin.utilisateurs.update', $user) : route('admin.utilisateurs.store');
    $formMethod = $isEdit ? 'patch' : 'post';
    $selectedRoles = old('roles', $isEdit ? $user->adminRoles() : [\App\Support\AdminAccess::MEMBER]);
    $availableRoles = collect($roleOptions)->reject(fn (string $label, string $value) => in_array($value, $selectedRoles, true));
    $visibleErrors = $errors->any() ? collect($errors->all())->unique()->take(6) : collect();
@endphp

<form class="admin-mission-form" action="{{ $formAction }}" method="post" enctype="multipart/form-data" data-real-form>
    @csrf
    @if($formMethod !== 'post')
        @method($formMethod)
    @endif

    @if ($errors->any())
        <div class="admin-form-error-summary" role="alert">
            <strong>Formulaire incomplet</strong>
            <ul>
                @foreach ($visibleErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @if ($errors->count() > $visibleErrors->count())
                    <li>Corrige les autres champs signales puis reessaie.</li>
                @endif
            </ul>
        </div>
    @endif

    <section class="admin-form-section">
        <div class="admin-form-section-title">
            <span>1</span>
            <div>
                <h3>Identité</h3>
                <p>{{ $isEdit ? "Ces informations sont visibles dans l'administration." : 'Le compte sera ajouté à la liste des utilisateurs.' }}</p>
            </div>
        </div>

        <div class="admin-form-grid admin-form-grid--user">
            <label class="admin-field" for="u-name">
                <span>Pseudo</span>
                <input id="u-name" name="name" type="text" value="{{ old('name', $user->name) }}" @unless($isEdit) placeholder="Ex: Pandawa" @endunless required>
            </label>

            <label class="admin-field" for="u-email">
                <span>Email</span>
                <input id="u-email" name="email" type="email" value="{{ old('email', $user->email) }}" @unless($isEdit) placeholder="pseudo@leszheros.fr" @endunless required>
            </label>

            @if($isEdit)
                <label class="admin-toggle-field">
                    <input type="checkbox" name="is_approved" value="1" @checked(old('is_approved', $user->is_approved))>
                    <span>Compte validé</span>
                </label>

                <div class="admin-user-avatar-editor">
                    <span class="admin-user-avatar admin-user-avatar--large">
                        @if ($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="Photo de {{ $user->name }}">
                        @else
                            {{ $user->initials() }}
                        @endif
                    </span>
                    <label class="admin-field" for="u-avatar">
                        <span>Photo de profil</span>
                        <input id="u-avatar" name="avatar" type="file" accept="image/*">
                    </label>
                    @if ($user->avatarUrl())
                        <label class="admin-toggle-field admin-user-avatar-remove">
                            <input type="checkbox" name="remove_avatar" value="1">
                            <span>Supprimer la photo</span>
                        </label>
                    @endif
                </div>
            @else
                <label class="admin-field admin-field--file" for="u-avatar">
                    <span>Photo de profil</span>
                    <input id="u-avatar" name="avatar" type="file" accept="image/*">
                </label>
            @endif

            @if ($canManageUserRoles)
                <div class="admin-user-role-board admin-transfer-board" data-user-role-board>
                    <section class="admin-transfer-board__column admin-transfer-board__column--available">
                        <div class="admin-transfer-board__head">
                            <h4>Rôles disponibles</h4>
                            <span>À ajouter</span>
                        </div>
                        <div class="admin-transfer-board__list" data-user-role-list="available" aria-label="Rôles disponibles">
                            @foreach ($availableRoles as $roleValue => $roleLabel)
                                <button class="admin-transfer-board__chip" type="button" draggable="true" data-user-role-chip="{{ $roleValue }}">
                                    <i class="fa-solid fa-grip-vertical"></i>
                                    <span>{{ $roleLabel }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <section class="admin-transfer-board__column admin-transfer-board__column--selected">
                        <div class="admin-transfer-board__head">
                            <h4>Rôles de l'utilisateur</h4>
                            <span>Actifs</span>
                        </div>
                        <div class="admin-transfer-board__list" data-user-role-list="selected" aria-label="Rôles de l'utilisateur">
                            <p class="admin-transfer-board__empty" data-user-role-empty>Glisse les rôles ici.</p>
                            @foreach ($selectedRoles as $roleValue)
                                @if (isset($roleOptions[$roleValue]))
                                    <button class="admin-transfer-board__chip" type="button" draggable="true" data-user-role-chip="{{ $roleValue }}">
                                        <i class="fa-solid fa-grip-vertical"></i>
                                        <span>{{ $roleOptions[$roleValue] }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                        <div data-user-role-inputs></div>
                    </section>
                </div>
            @elseif($isEdit)
                <div class="admin-user-role-board admin-transfer-board">
                    <section class="admin-transfer-board__column admin-transfer-board__column--selected">
                        <div class="admin-transfer-board__head">
                            <h4>Rôles de l'utilisateur</h4>
                            <span>Lecture seule</span>
                        </div>
                        <div class="admin-transfer-board__list" aria-label="Rôles de l'utilisateur">
                            @foreach ($selectedRoles as $roleValue)
                                @if (isset($roleOptions[$roleValue]))
                                    <span class="admin-transfer-board__chip">
                                        <span>{{ $roleOptions[$roleValue] }}</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </section>
                </div>
            @else
                <input type="hidden" name="roles[]" value="{{ \App\Support\AdminAccess::MEMBER }}">
            @endif
        </div>
    </section>

    <section class="admin-form-section">
        <div class="admin-form-section-title">
            <span>2</span>
            <div>
                <h3>Sécurité</h3>
                <p>{{ $isEdit ? 'Laisse vide si tu ne veux pas changer le mot de passe.' : 'Tu peux saisir un mot de passe ou le générer automatiquement.' }}</p>
            </div>
        </div>

        <div class="admin-form-grid admin-form-grid--user">
            <label class="admin-field admin-field--with-action" for="u-password">
                <span>{{ $isEdit ? 'Nouveau mot de passe' : 'Mot de passe' }}</span>
                <span class="admin-field-action">
                    <input id="u-password" name="password" type="text" placeholder="{{ $isEdit ? 'Nouveau mot de passe' : 'Mot de passe' }}" minlength="6" @unless($isEdit) required @endunless data-password-input>
                    <button class="admin-secondary-button" type="button" data-generate-password>
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Générer</span>
                    </button>
                </span>
            </label>
        </div>
    </section>

    <div class="admin-form-actions">
        <a class="admin-secondary-button" href="{{ route('admin.utilisateurs.index') }}">
            <i class="fa-solid fa-xmark"></i>
            <span>Annuler</span>
        </a>
        <button class="admin-create-button" type="submit">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>{{ $isEdit ? 'Enregistrer les modifications' : 'Créer' }}</span>
        </button>
    </div>
</form>

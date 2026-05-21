<section class="admin-form-section">
    <div class="admin-form-section-title">
        <span>1</span>
        <div>
            <h3>Mission</h3>
            <p>Choisis la mission et le joueur principal.</p>
        </div>
    </div>

    <div class="admin-form-grid admin-form-grid--validation">
        <label class="admin-field" for="validation-mission">
            <span>Mission</span>
            <select id="validation-mission" name="mission_id" required>
                <option value="">Choisir une mission</option>
                @foreach ($missions as $mission)
                    <option value="{{ $mission->id }}" @selected((int) old('mission_id', $validation->mission_id) === $mission->id)>{{ $mission->title }}</option>
                @endforeach
            </select>
        </label>

        <label class="admin-field" for="validation-player">
            <span>Joueur</span>
            <select id="validation-player" name="user_id" required>
                <option value="">Choisir un joueur</option>
                @foreach ($players as $player)
                    <option value="{{ $player->id }}" @selected((int) old('user_id', $validation->user_id) === $player->id)>{{ $player->name }}</option>
                @endforeach
            </select>
        </label>
    </div>
</section>

<section class="admin-form-section">
    <div class="admin-form-section-title">
        <span>2</span>
        <div>
            <h3>Validation</h3>
            <p>Indique la participation et le statut.</p>
        </div>
    </div>

    <div class="admin-validation-entry">
        <div class="admin-form-grid admin-form-grid--validation">
            <label class="admin-field" for="validation-characters">
                <span>Personnages du joueur</span>
                <input id="validation-characters" name="characters" type="number" min="1" max="8" value="{{ old('characters', $validation->characters ?? 1) }}" required>
            </label>

            @unless (($showTeammateBuilder ?? false) === true)
                <label class="admin-field" for="validation-status">
                    <span>Statut</span>
                    <select id="validation-status" name="status">
                        @foreach (\App\Models\MissionValidation::STATUSES as $status => $label)
                            <option value="{{ $status }}" @selected(old('status', $validation->status ?? \App\Models\MissionValidation::PENDING) === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endunless
        </div>
    </div>
</section>

@php($groupValidations = $groupValidations ?? collect())
@if ($groupValidations->count() > 1)
    <section class="admin-form-section">
        <div class="admin-form-section-title">
            <span>3</span>
            <div>
                <h3>Groupe lie</h3>
                <p>Cette declaration contient une aide. Tu peux modifier les participants lies en meme temps.</p>
            </div>
        </div>

        <div class="admin-validation-group">
            <label class="admin-toggle-field">
                <input type="checkbox" name="sync_group" value="1" checked>
                <span>Modifier tout le groupe lie</span>
            </label>

            <div class="admin-validation-group__rows">
                @foreach ($groupValidations as $groupValidation)
                    <div class="admin-validation-group__row">
                        <strong>{{ $groupValidation->user?->name ?? 'Utilisateur supprimé' }}</strong>
                        <label class="admin-field" for="group-validation-{{ $groupValidation->id }}-characters">
                            <span>Personnages</span>
                            <input id="group-validation-{{ $groupValidation->id }}-characters" name="group_validations[{{ $groupValidation->id }}][characters]" type="number" min="1" max="8" value="{{ old("group_validations.{$groupValidation->id}.characters", $groupValidation->characters) }}">
                        </label>
                        <label class="admin-field" for="group-validation-{{ $groupValidation->id }}-status">
                            <span>Statut</span>
                            <select id="group-validation-{{ $groupValidation->id }}-status" name="group_validations[{{ $groupValidation->id }}][status]">
                                @foreach (\App\Models\MissionValidation::STATUSES as $status => $label)
                                    <option value="{{ $status }}" @selected(old("group_validations.{$groupValidation->id}.status", $groupValidation->status) === $status)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@elseif (($showTeammateBuilder ?? false) === true)
    <section class="admin-form-section">
        <div class="admin-form-section-title">
            <span>3</span>
            <div>
                <h3>Coéquipiers</h3>
                <p>Ajoute les joueurs aidés ou présents sur la déclaration.</p>
            </div>
        </div>

        <div class="admin-validation-group" data-validation-teammates-builder>
            <div class="admin-validation-group__rows" data-validation-teammates-list>
                <div class="admin-validation-group__row">
                    <strong>Coéquipier</strong>
                    <label class="admin-field">
                        <span>Joueur</span>
                        <select name="teammate_user_id[]">
                            <option value="">Aucun</option>
                            @foreach ($players as $player)
                                <option value="{{ $player->id }}">{{ $player->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="admin-field">
                        <span>Personnages</span>
                        <input name="teammate_characters[]" type="number" min="1" max="8" value="1">
                    </label>
                </div>
            </div>

            <button class="admin-secondary-button" type="button" data-validation-add-teammate>
                <i class="fa-solid fa-plus"></i>
                <span>Ajouter un coéquipier</span>
            </button>
        </div>
    </section>
@endif

<div class="admin-form-actions">
    <a class="admin-secondary-button" href="{{ route('admin.validations.index') }}">
        <i class="fa-solid fa-xmark"></i>
        <span>Annuler</span>
    </a>
    <button class="admin-create-button" type="submit">
        <i class="fa-solid fa-floppy-disk"></i>
        <span>Enregistrer</span>
    </button>
</div>

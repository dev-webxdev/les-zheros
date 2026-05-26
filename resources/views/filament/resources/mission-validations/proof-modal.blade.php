<div class="lz-proof-modal">
    <div class="lz-proof-modal__meta">
        <div>
            <span>Joueur</span>
            <strong>{{ $record->user?->name ?? 'Utilisateur supprime' }}</strong>
        </div>

        <div>
            <span>Mission</span>
            <strong>{{ $record->mission?->title ?? 'Mission supprimee' }}</strong>
        </div>

        <div>
            <span>Statut</span>
            <strong>{{ $record->statusLabel() }}</strong>
        </div>
    </div>

    @if ($proofUrl)
        <a href="{{ $proofUrl }}" target="_blank" rel="noopener" class="lz-proof-modal__image-link" title="Ouvrir dans un nouvel onglet">
            <img src="{{ $proofUrl }}" alt="Preuve de validation">
        </a>
    @endif

    @if ($record->proof_text && $record->proof_text !== $proofUrl)
        <div class="lz-proof-modal__text">
            {{ $record->proof_text }}
        </div>
    @endif
</div>

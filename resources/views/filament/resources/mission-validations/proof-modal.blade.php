<div class="space-y-4">
    <div class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <span class="block text-xs font-semibold uppercase text-gray-500">Joueur</span>
            <strong>{{ $record->user?->name ?? 'Utilisateur supprime' }}</strong>
        </div>

        <div>
            <span class="block text-xs font-semibold uppercase text-gray-500">Mission</span>
            <strong>{{ $record->mission?->title ?? 'Mission supprimee' }}</strong>
        </div>
    </div>

    @if ($proofUrl)
        <a href="{{ $proofUrl }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
            <img src="{{ $proofUrl }}" alt="Preuve de validation" class="mx-auto max-h-[72vh] w-auto max-w-full object-contain">
        </a>
    @endif

    @if ($record->proof_text && $record->proof_text !== $proofUrl)
        <div class="rounded-lg border border-gray-200 bg-white p-3 text-sm">
            {{ $record->proof_text }}
        </div>
    @endif
</div>

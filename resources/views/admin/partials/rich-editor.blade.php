@props([
    'id',
    'name',
    'value' => '',
    'placeholder' => 'Rédige ton contenu...',
    'surfaceClass' => '',
])

<div class="admin-rich-editor admin-rich-editor--compact" data-rich-editor>
    <div class="admin-rich-editor__toolbar" aria-label="Outils de mise en forme">
        <button type="button" data-editor-command="bold" title="Gras"><i class="fa-solid fa-bold"></i></button>
        <button type="button" data-editor-command="italic" title="Italique"><i class="fa-solid fa-italic"></i></button>
        <button type="button" data-editor-command="underline" title="Souligné"><i class="fa-solid fa-underline"></i></button>
        <button type="button" data-editor-command="insertUnorderedList" title="Liste à puces"><i class="fa-solid fa-list-ul"></i></button>
        <button type="button" data-editor-command="insertOrderedList" title="Liste numérotée"><i class="fa-solid fa-list-ol"></i></button>
        <button type="button" data-editor-command="formatBlock" data-editor-value="blockquote" title="Citation"><i class="fa-solid fa-quote-right"></i></button>
        <button type="button" data-editor-link title="Lien"><i class="fa-solid fa-link"></i></button>
    </div>
    <div class="admin-rich-editor__surface {{ $surfaceClass }}" contenteditable="true" data-editor-surface data-placeholder="{{ $placeholder }}">{!! old(str_replace(['[', ']'], ['.', ''], $name), $value) !!}</div>
    <textarea id="{{ $id }}" name="{{ $name }}" data-editor-input hidden>{{ old(str_replace(['[', ']'], ['.', ''], $name), $value) }}</textarea>
</div>

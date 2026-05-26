@php
    $class = $class ?? null;
    $placeholder = $placeholder ?? null;
    $required = $required ?? false;
    $baseClass = $baseClass ?? 'admin-field';
    $fieldAttributes = $fieldAttributes ?? '';
    $textareaAttributes = $textareaAttributes ?? '';
@endphp

<label @class([$baseClass, $class]) for="{{ $id }}" {!! $fieldAttributes !!}>
    <span>{{ $label }}</span>
    <textarea id="{{ $id }}" name="{{ $name }}" @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($required) required @endif {!! $textareaAttributes !!}>{{ $value ?? '' }}</textarea>
</label>

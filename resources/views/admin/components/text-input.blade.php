@php
    $class = $class ?? null;
    $type = $type ?? 'text';
    $value = $value ?? null;
    $placeholder = $placeholder ?? null;
    $required = $required ?? false;
    $baseClass = $baseClass ?? 'admin-field';
    $fieldAttributes = $fieldAttributes ?? '';
    $inputAttributes = $inputAttributes ?? '';
@endphp

<label @class([$baseClass, $class]) for="{{ $id }}" {!! $fieldAttributes !!}>
    <span>{{ $label }}</span>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($required) required @endif {!! $inputAttributes !!}>
</label>

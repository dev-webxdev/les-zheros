@php
    $class = $class ?? null;
    $required = $required ?? false;
    $fieldAttributes = $fieldAttributes ?? '';
    $selectAttributes = $selectAttributes ?? '';
@endphp

<label @class(['admin-field', $class]) for="{{ $id }}" {!! $fieldAttributes !!}>
    <span>{{ $label }}</span>
    <select id="{{ $id }}" name="{{ $name }}" @if($required) required @endif {!! $selectAttributes !!}>
        {{ $slot }}
    </select>
</label>

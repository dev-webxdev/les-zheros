@php
    $class = $class ?? null;
    $label = $label ?? null;
@endphp

<span @class(['admin-tag', $class])>{{ $label ?? $slot }}</span>

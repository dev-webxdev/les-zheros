@php($class = $class ?? null)

<div @class(['admin-row-actions', $class])>
    {{ $slot }}
</div>

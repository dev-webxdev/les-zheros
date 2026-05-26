@php($class = $class ?? null)

<div @class(['admin-table-card', $class])>
    {{ $slot }}
</div>

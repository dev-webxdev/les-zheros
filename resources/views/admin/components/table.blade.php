@php($class = $class ?? null)

<table @class(['admin-table', $class])>
    {{ $slot }}
</table>

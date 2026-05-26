@php($class = $class ?? null)

<div @class(['admin-form-actions', $class])>
    {{ $slot }}
</div>

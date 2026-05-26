@php
    $class = $class ?? null;
    $icon = $icon ?? null;
    $text = $text ?? null;
@endphp

<div @class(['admin-empty-state', $class])>
    @if($icon)<i class="{{ $icon }}"></i>@endif
    <strong>{{ $title }}</strong>
    @if($text)<span>{{ $text }}</span>@endif
    {{ $slot ?? '' }}
</div>

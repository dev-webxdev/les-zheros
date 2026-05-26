@php
    $href = $href ?? null;
    $type = $type ?? 'button';
    $class = $class ?? 'admin-secondary-button';
    $icon = $icon ?? null;
    $label = $label ?? null;
@endphp

@if($href)
    <a class="{{ $class }}" href="{{ $href }}">
        @if($icon)<i class="{{ $icon }}"></i>@endif
        @if($label)<span>{{ $label }}</span>@endif
        {{ $slot ?? '' }}
    </a>
@else
    <button class="{{ $class }}" type="{{ $type }}">
        @if($icon)<i class="{{ $icon }}"></i>@endif
        @if($label)<span>{{ $label }}</span>@endif
        {{ $slot ?? '' }}
    </button>
@endif

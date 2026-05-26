@php
    $class = $class ?? null;
    $number = $number ?? null;
    $title = $title ?? null;
    $description = $description ?? null;
@endphp

<section @class(['admin-form-section', $class])>
    @if($number || $title || $description)
        <div class="admin-form-section-title">
            @if($number)<span>{{ $number }}</span>@endif
            <div>
                @if($title)<h3>{{ $title }}</h3>@endif
                @if($description)<p>{{ $description }}</p>@endif
            </div>
        </div>
    @endif

    {{ $slot }}
</section>

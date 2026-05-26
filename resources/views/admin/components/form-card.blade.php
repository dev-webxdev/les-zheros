@php
    $class = $class ?? null;
    $titleId = $titleId ?? null;
    $title = $title ?? null;
    $description = $description ?? null;
@endphp

<section @class(['admin-form-card', $class]) @if($titleId) aria-labelledby="{{ $titleId }}" @endif>
    @if($title || $description)
        <div class="admin-form-head">
            <div>
                @if($title)<h2 @if($titleId) id="{{ $titleId }}" @endif>{{ $title }}</h2>@endif
                @if($description)<p>{{ $description }}</p>@endif
            </div>
        </div>
    @endif

    {{ $slot }}
</section>

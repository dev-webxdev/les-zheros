@props([
    'id',
    'action',
    'method' => 'post',
    'actions' => [],
    'label' => 'Action groupée',
])

@if (count($actions) > 0)
    <form id="{{ $id }}" class="admin-bulk-actions" action="{{ $action }}" method="post" data-bulk-form>
        @csrf
        @if (! in_array(strtolower($method), ['get', 'post'], true))
            @method($method)
        @endif
        <label class="admin-bulk-actions__check">
            <input type="checkbox" data-bulk-check-all="{{ $id }}">
            <span>Tout sélectionner</span>
        </label>
        <span data-bulk-count>0 sélectionné</span>
        <select name="action" aria-label="{{ $label }}" required>
            <option value="">{{ $label }}</option>
            @foreach ($actions as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
            @endforeach
        </select>
        <button class="admin-secondary-button" type="submit" disabled data-bulk-submit>
            <i class="fa-solid fa-check"></i>
            <span>Appliquer</span>
        </button>
    </form>
@endif

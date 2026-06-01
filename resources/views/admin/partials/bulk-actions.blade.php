@props([
    'id',
    'action',
    'method' => 'post',
    'actions' => [],
    'label' => 'Action groupee',
    'filterFields' => [],
    'filteredLabel' => null,
])

@if (count($actions) > 0)
    <form id="{{ $id }}" class="admin-bulk-actions" action="{{ $action }}" method="post" data-bulk-form>
        @csrf
        @if (! in_array(strtolower($method), ['get', 'post'], true))
            @method($method)
        @endif
        @foreach ($filterFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <label class="admin-bulk-actions__check">
            <input type="checkbox" data-bulk-check-all="{{ $id }}">
            <span>Tout selectionner</span>
        </label>
        @if($filteredLabel)
            <label class="admin-bulk-actions__check">
                <input type="checkbox" name="scope" value="filtered" data-bulk-filtered-scope>
                <span>{{ $filteredLabel }}</span>
            </label>
        @endif
        <span data-bulk-count>0 selectionne</span>
        <select name="action" aria-label="{{ $label }}" autocomplete="off" required>
            <option value="" selected disabled>{{ $label }}</option>
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

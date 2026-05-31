@php($class = $class ?? null)
@php($canDelete = $canDelete ?? false)

@component('admin.components.table-actions', ['class' => $class])
    <form action="{{ $restoreUrl }}" method="post" data-real-form>
        @csrf
        @method('patch')
        <button class="admin-action-button admin-action-button--restore" type="submit" @isset($restoreAria) aria-label="{{ $restoreAria }}" @endisset title="Restaurer"><i class="fa-solid fa-rotate-left"></i></button>
    </form>
    @if($canDelete)
        <form action="{{ $deleteUrl }}" method="post" data-real-form>
            @csrf
            @method('delete')
            <button class="admin-action-button admin-action-button--delete" type="submit" @isset($deleteAria) aria-label="{{ $deleteAria }}" @endisset title="Supprimer definitivement"><i class="fa-regular fa-trash-can"></i></button>
        </form>
    @endif
@endcomponent

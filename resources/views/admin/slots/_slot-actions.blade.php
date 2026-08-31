@php
    $slotActionId = $slotId ?? ($slot ?? null)?->id ?? null;
    $canDeleteSlot = $canDelete ?? true;
    $groupClass = $class ?? 'slot-action-group';
@endphp

@if($slotActionId)
    @canany(['view_slots', 'update_slots', 'delete_slots'])
        <div class="{{ $groupClass }}">
            @can('view_slots')
                <a class="btn btn-sm btn-outline-secondary slot-action-button" href="{{ route('admin.slots.show', $slotActionId) }}" title="جزئیات تایم">
                    <i class="ri-eye-line align-middle"></i>
                    <span class="visually-hidden">جزئیات تایم</span>
                </a>
            @endcan

            @can('update_slots')
                <a class="btn btn-sm btn-outline-primary slot-action-button" href="{{ route('admin.slots.edit', $slotActionId) }}" title="ویرایش تایم">
                    <i class="ri-edit-line align-middle"></i>
                    <span class="visually-hidden">ویرایش تایم</span>
                </a>
            @endcan

            @can('delete_slots')
                @if($canDeleteSlot)
                    <form method="post" action="{{ route('admin.slots.destroy', $slotActionId) }}" class="slot-delete-form" onsubmit="return confirm('این تایم حذف شود؟ اگر اشتباه ایجاد شده و رزرو فعالی ندارد، قابل حذف است.');">
                        @csrf
                        @method('delete')
                        <button class="btn btn-sm btn-outline-danger slot-action-button" type="submit" title="حذف تایم">
                            <i class="ri-delete-bin-line align-middle"></i>
                            <span class="visually-hidden">حذف تایم</span>
                        </button>
                    </form>
                @else
                    <button class="btn btn-sm btn-outline-danger slot-action-button" type="button" disabled title="این تایم رزرو فعال دارد و قابل حذف نیست.">
                        <i class="ri-delete-bin-line align-middle"></i>
                        <span class="visually-hidden">حذف تایم</span>
                    </button>
                @endif
            @endcan
        </div>
    @endcanany
@endif

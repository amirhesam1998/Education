<tr class="azad-row" data-item-row data-item-id="{{ $item->id }}" data-program-id="{{ $item->azad_program_id }}" @if($editable) draggable="true" @endif>
    @if($editable)
        <td class="text-nowrap">
            <button type="button" class="azad-move" data-drag title="بکشید و رها کنید" aria-label="جابه‌جایی"><i class="ri-draggable"></i></button>
            <button type="button" class="azad-move" data-move-up title="یک رده بالاتر" aria-label="یک رده بالاتر"><i class="ri-arrow-up-s-line"></i></button>
            <button type="button" class="azad-move" data-move-down title="یک رده پایین‌تر" aria-label="یک رده پایین‌تر"><i class="ri-arrow-down-s-line"></i></button>
        </td>
    @endif
    <td><span class="azad-number" data-order>{{ \App\Support\PersianDate::number($item->priority_order) }}</span></td>
    <td class="azad-code ltr">{{ $item->unit_code }}</td>
    <td>{{ $item->unit_name }}</td>
    <td class="azad-code ltr">{{ $item->field_code }}</td>
    <td>{{ $item->field_name }}@if($item->part_time) (پاره وقت)@endif</td>
    <td>{{ $item->bookletLabel() }}</td>
    <td>{{ $item->province }}<small class="d-block text-muted">{{ $item->city }}</small></td>
    <td>{{ $item->gender }}</td>
    <td class="text-nowrap">@if($item->admission === 'exam'){{ $item->capacity_first ?? '-' }} / {{ $item->capacity_second ?? '-' }}@else<span class="text-muted">سوابق</span>@endif</td>
    @if($editable)
        <td><input class="form-control form-control-sm azad-note" data-note data-url="{{ route('admin.azad-selection-items.update', $item) }}" value="{{ $item->note }}" maxlength="1000" placeholder="توضیح"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-delete data-url="{{ route('admin.azad-selection-items.destroy', $item) }}" title="حذف"><i class="ri-delete-bin-line"></i></button></td>
    @else
        <td>{{ $item->note ?: '-' }}</td>
    @endif
</tr>

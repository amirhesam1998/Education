<tr class="selection-row" data-item-row data-item-id="{{ $item->id }}" @if($editable) draggable="true" @endif>
    @if($editable)<td><button type="button" class="drag-handle" title="جابه‌جایی ردیف" aria-label="جابه‌جایی ردیف"><i class="ri-draggable"></i></button></td>@endif
    <td><span class="selection-number" data-order>{{ \App\Support\PersianDate::number($item->priority_order) }}</span><input form="bulk-update-form" type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}"></td>
    @if($editable)
        <td><input form="bulk-update-form" name="items[{{ $item->id }}][field_code]" value="{{ $item->field_code }}" class="form-control selection-input" data-item-input data-catalog-input data-field="field_code" required></td>
        <td><input form="bulk-update-form" name="items[{{ $item->id }}][field_name]" value="{{ $item->field_name }}" class="form-control selection-input" data-item-input data-catalog-input data-field="field_name" required></td>
        <td><input form="bulk-update-form" name="items[{{ $item->id }}][city]" value="{{ $item->city }}" class="form-control selection-input" data-item-input data-field="city" required></td>
        <td><div class="selection-actions"><button class="btn btn-sm btn-outline-primary" type="submit" form="bulk-update-form" title="ذخیره تغییرات"><i class="ri-save-line"></i></button><button class="btn btn-sm btn-outline-danger" type="button" data-delete-item data-delete-url="{{ route('admin.field-selection-items.destroy', $item) }}" title="حذف"><i class="ri-delete-bin-line"></i></button></div></td>
    @else
        <td>{{ $item->field_code }}</td><td>{{ $item->field_name }}</td><td>{{ $item->city }}</td>
    @endif
</tr>

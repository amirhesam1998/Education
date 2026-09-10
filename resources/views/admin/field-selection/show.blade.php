@extends('layouts.admin')

@section('title', 'مدیریت انتخاب رشته')

@push('styles')
<style>
    .selection-toolbar{position:sticky;top:1rem;z-index:10}.selection-summary{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}.selection-counter{font-size:.82rem;color:var(--ink-500)}.selection-dirty{display:none;font-size:.78rem;color:var(--warning);font-weight:600}.selection-dirty.is-visible{display:inline-flex;align-items:center;gap:.25rem}.selection-search{max-width:370px}.selection-columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.selection-column{border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;background:var(--surface)}.selection-column-head{padding:.8rem 1rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.88rem;font-weight:700}.selection-table{width:100%;min-width:1080px;border-collapse:collapse}.selection-table th{font-size:.74rem;color:var(--ink-500);font-weight:600;background:var(--surface);white-space:nowrap}.selection-table th,.selection-table td{padding:.5rem;border-bottom:1px solid var(--border);vertical-align:middle}.selection-row{transition:background-color .15s ease,box-shadow .15s ease}.selection-row:hover{background:var(--brand-50)}.selection-row.is-changed{background:#fff8e7;box-shadow:inset 3px 0 0 var(--warning)}.selection-row.dragging{opacity:.42}.selection-list.is-over{background:var(--brand-50)}.selection-number{width:2rem;height:2rem;border-radius:50%;background:var(--brand-50);color:var(--brand-700);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem}.drag-handle{border:0;background:transparent;color:var(--ink-500);cursor:grab;padding:.3rem}.drag-handle:active{cursor:grabbing}.selection-input{min-width:100px;padding:.45rem .55rem;min-height:36px;font-size:.82rem}.selection-textarea{min-width:210px;resize:vertical;line-height:1.7}.selection-actions{display:flex;gap:.25rem;white-space:nowrap}.catalog-menu{position:absolute;z-index:1000;width:min(520px,calc(100vw - 2rem));background:var(--surface);border:1px solid var(--border);box-shadow:var(--shadow-md);border-radius:var(--radius-sm);overflow:hidden}.catalog-option{display:block;width:100%;border:0;border-bottom:1px solid var(--border);background:var(--surface);text-align:right;padding:.65rem .8rem;font-size:.8rem}.catalog-option:hover{background:var(--brand-50)}.catalog-option:last-child{border-bottom:0}.catalog-option small{display:block;color:var(--ink-500);margin-top:.15rem}.catalog-filter-grid{display:grid;grid-template-columns:minmax(220px,1.4fr) repeat(3,minmax(160px,1fr)) auto;gap:.75rem;align-items:end}.catalog-help{font-size:.8rem;color:var(--ink-500);margin-top:.7rem}.catalog-results{max-height:390px;overflow:auto;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)}.catalog-result{display:grid;grid-template-columns:96px minmax(230px,1fr) minmax(210px,.9fr) minmax(220px,1fr) 112px;gap:.75rem;align-items:center;padding:.8rem .9rem;border-bottom:1px solid var(--border);font-size:.85rem}.catalog-result:last-child{border-bottom:0}.catalog-result:hover{background:var(--brand-50)}.catalog-code{font-weight:700;color:var(--brand-700)}.catalog-title{display:block;margin-bottom:.25rem}.catalog-meta,.catalog-place{display:flex;flex-wrap:wrap;gap:.25rem .5rem;color:var(--ink-500);font-size:.78rem}.catalog-chip{display:inline-flex;align-items:center;gap:.2rem;border:1px solid var(--border);border-radius:999px;padding:.12rem .45rem;background:var(--bg);white-space:nowrap}.catalog-state{padding:1rem;color:var(--ink-500);font-size:.84rem}.catalog-state.loading{color:var(--brand-700)}.add-row td{background:#f8fbff}.duplicate-code{border-color:var(--warning)!important;background:#fff8e7}.empty-selection{padding:2rem 1rem;text-align:center;color:var(--ink-500)}.selection-toast{position:fixed;left:1rem;bottom:1rem;z-index:1100;display:none;max-width:340px;padding:.8rem 1rem;border-radius:var(--radius-sm);color:#fff;box-shadow:var(--shadow-md);font-size:.85rem}.selection-toast.show{display:block}.selection-toast.success{background:var(--success)}.selection-toast.warning{background:var(--warning)}.selection-toast.danger{background:var(--danger)}@media(max-width:1199.98px){.catalog-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.catalog-filter-actions{grid-column:1 / -1}.catalog-result{grid-template-columns:90px 1fr auto}.catalog-result-place{grid-column:2 / 3}.catalog-result-action{grid-row:1 / span 2;grid-column:3}}@media(max-width:991.98px){.selection-toolbar{position:static}.selection-columns{grid-template-columns:1fr}}@media(max-width:575.98px){.selection-search{max-width:none;width:100%}.selection-toolbar .btn{flex:1}.selection-table{min-width:1080px}.catalog-filter-grid{grid-template-columns:1fr}.catalog-result{grid-template-columns:1fr}.catalog-result-place,.catalog-result-action{grid-column:auto;grid-row:auto}.catalog-result-action .btn{width:100%}}
</style>
@endpush

@section('content')
    @php($editable = $plan->isDraft() && auth()->user()->can('manage_field_selection'))
    @php($itemsCount = $plan->items->count())
    <form id="bulk-update-form" method="post" action="{{ route('admin.field-selection-plans.bulk-update', $plan) }}">@csrf</form>
    <form id="add-item-form" method="post" action="{{ route('admin.field-selection-plans.items.store', $plan) }}">@csrf</form>
    <form id="reorder-form" method="post" action="{{ route('admin.field-selection-plans.reorder', $plan) }}">@csrf</form>

    <div class="card selection-toolbar mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="selection-summary">
                <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.show', $reservation) }}" title="بازگشت به رزرو"><i class="ri-arrow-right-line"></i><span class="d-none d-sm-inline">بازگشت به رزرو</span></a>
                <div><strong>لیست انتخاب رشته</strong><div class="selection-counter">نسخه {{ \App\Support\PersianDate::number($plan->version) }} | <span id="item-counter">{{ \App\Support\PersianDate::number($itemsCount) }}</span> از ۱۵۰ مورد</div></div>
                <span class="badge text-bg-{{ $plan->isPublished() ? 'success' : ($plan->status === \App\Models\FieldSelectionPlan::STATUS_ARCHIVED ? 'secondary' : 'warning') }}">{{ $plan->status === 'published' ? 'منتشر شده' : ($plan->status === 'archived' ? 'آرشیو شده' : 'پیش‌نویس') }}</span>
                @if($plan->canBePubliclyVisible())<span class="badge text-bg-{{ $plan->is_public_visible ? 'info' : 'light' }}">{{ $plan->is_public_visible ? 'قابل مشاهده برای دانش‌آموز' : 'مخفی از دانش‌آموز' }}</span>@endif
                @if($editable)<span class="selection-dirty" id="selection-dirty"><i class="ri-error-warning-line"></i> تغییرات ذخیره نشده دارید</span>@endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.field-selection-plans.print', $plan) }}"><i class="ri-printer-line"></i> چاپ انتخاب رشته</a>
                @can('manage_field_selection')
                    @if($plan->canBePubliclyVisible())
                        <form method="post" action="{{ route($plan->is_public_visible ? 'admin.field-selection-plans.hide-from-student' : 'admin.field-selection-plans.show-to-student', $plan) }}">
                            @csrf
                            <button class="btn btn-outline-{{ $plan->is_public_visible ? 'danger' : 'success' }}" onclick="return confirm('{{ $plan->is_public_visible ? 'آیا این نسخه از دید دانش‌آموز مخفی شود؟' : 'آیا این نسخه برای دانش‌آموز قابل مشاهده شود؟' }}')" type="submit">
                                {{ $plan->is_public_visible ? 'مخفی کردن از دانش‌آموز' : 'نمایش به دانش‌آموز' }}
                            </button>
                        </form>
                    @endif
                @endcan
                @if($editable)
                    <button class="btn btn-outline-primary" type="submit" form="reorder-form" id="save-order" @disabled($itemsCount === 0)><i class="ri-list-check-2"></i> ذخیره ترتیب</button>
                    <button class="btn btn-primary" type="submit" form="bulk-update-form" id="save-changes" @disabled($itemsCount === 0)><i class="ri-save-line"></i> ذخیره تغییرات</button>
                    <form method="post" action="{{ route('admin.field-selection-plans.publish', $plan) }}" id="publish-form" data-confirm="آیا از انتشار این لیست انتخاب رشته مطمئن هستید؟">@csrf<button class="btn btn-success" id="publish-selection" @disabled($itemsCount === 0) title="{{ $itemsCount === 0 ? 'برای انتشار حداقل یک رشته ثبت کنید.' : '' }}"><i class="ri-send-plane-line"></i> انتشار لیست</button></form>
                @elseif($plan->isPublished())
                    @can('manage_field_selection')<form method="post" action="{{ route('admin.field-selection-plans.new-version', $plan) }}">@csrf<button class="btn btn-primary"><i class="ri-edit-2-line"></i> ویرایش رشته</button></form>@endcan
                @endif
            </div>
        </div>
    </div>

    @if($plan->isPublished() && ! $plan->is_public_visible)
        <div class="alert alert-warning py-2">این نسخه منتشر شده است اما هنوز برای دانش‌آموز قابل مشاهده نیست.</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3 small text-muted">
        <span>کل نسخه‌ها: {{ \App\Support\PersianDate::number($planVisibilityStats['total']) }}</span>
        <span>نسخه‌های منتشر شده: {{ \App\Support\PersianDate::number($planVisibilityStats['published']) }}</span>
        <span>نسخه‌های قابل مشاهده برای دانش‌آموز: {{ \App\Support\PersianDate::number($planVisibilityStats['student_visible']) }}</span>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="ri-search-line"></i> جستجوی رشتهمحل از دیتابیس</div>
        <div class="card-body">
            <div class="catalog-filter-grid">
                <div>
                    <label class="form-label" for="catalog-field-query">نام رشته، کدرشته یا توضیحات</label>
                    <input class="form-control" id="catalog-field-query" placeholder="مثلاً پرستاری" autocomplete="off" @disabled(! $editable)>
                </div>
                <div>
                    <label class="form-label" for="catalog-province">استان</label>
                    <select class="form-select" id="catalog-province" @disabled(! $editable)>
                        <option value="">همه استان‌ها</option>
                        @foreach($catalogProvinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="catalog-city">شهر</label>
                    <select class="form-select" id="catalog-city" disabled>
                        <option value="">اول استان را انتخاب کنید</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="catalog-course-type">نوع دانشگاه / دوره</label>
                    <select class="form-select" id="catalog-course-type" @disabled(! $editable)>
                        <option value="">همه انواع</option>
                        @foreach($catalogCourseTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="catalog-filter-actions d-flex gap-2">
                    <button class="btn btn-primary" type="button" id="catalog-search-button" @disabled(! $editable)><i class="ri-search-line"></i> جستجو</button>
                    <button class="btn btn-outline-secondary" type="button" id="catalog-reset-button" @disabled(! $editable) title="پاک کردن فیلترها"><i class="ri-refresh-line"></i></button>
                </div>
            </div>
            <div class="catalog-help">رشته را تایپ کن؛ استان، شهر، نوع دانشگاه/دوره و منبع دفترچه (مثل دفترچه ریاضی یا تجربی) در نتایج نمایش داده می‌شود. از نتایج می‌توانی مستقیم به لیست اضافه کنی.</div>
            <div class="catalog-results mt-3" id="catalog-results"><div class="catalog-state">برای شروع، حداقل نام رشته را وارد کن یا یکی از فیلترها را انتخاب کن.</div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="ri-list-ordered"></i> لیست انتخاب رشته</span>
            <input class="form-control selection-search" id="selection-filter" placeholder="جستجو در کد رشته، نام رشته، دانشگاه، شهر، نوع دانشگاه یا توضیحات..." aria-label="جستجو در انتخاب رشته">
        </div>
        <div class="card-body">
            @if($itemsCount === 0)
                <div class="empty-selection"><i class="ri-inbox-archive-line d-block mb-2" style="font-size:2rem"></i><div>هنوز رشته‌ای برای این دانش‌آموز ثبت نشده است.</div>@if($editable)<button type="button" class="btn btn-primary mt-3" id="add-first-row"><i class="ri-add-line"></i> افزودن اولین رشته</button>@endif</div>
            @endif
            @if($itemsCount >= 150)<div class="alert alert-warning"><i class="ri-error-warning-line"></i><div>حداکثر تعداد انتخاب‌ها ۱۵۰ مورد است.</div></div>@endif

            <div class="selection-columns" id="selection-columns">
                <section class="selection-column"><div class="selection-column-head">ستون راست: ردیف ۱ تا ۷۵</div><div class="table-responsive"><table class="selection-table"><thead><tr>@if($editable)<th></th>@endif<th>ردیف</th><th>کد رشته</th><th>نام رشته</th><th>توضیحات رشته</th><th>دانشگاه</th><th>شهر</th><th>نوع دانشگاه</th>@if($editable)<th>عملیات</th>@endif</tr></thead><tbody class="selection-list" data-list="right">@foreach($plan->items->take(75) as $item) @include('admin.field-selection._item', ['item' => $item, 'editable' => $editable]) @endforeach @if($editable && $itemsCount < 75) @include('admin.field-selection._add-row') @endif</tbody></table></div></section>
                <section class="selection-column"><div class="selection-column-head">ستون چپ: ردیف ۷۶ تا ۱۵۰</div><div class="table-responsive"><table class="selection-table"><thead><tr>@if($editable)<th></th>@endif<th>ردیف</th><th>کد رشته</th><th>نام رشته</th><th>توضیحات رشته</th><th>دانشگاه</th><th>شهر</th><th>نوع دانشگاه</th>@if($editable)<th>عملیات</th>@endif</tr></thead><tbody class="selection-list" data-list="left">@foreach($plan->items->slice(75) as $item) @include('admin.field-selection._item', ['item' => $item, 'editable' => $editable]) @endforeach @if($editable && $itemsCount >= 75 && $itemsCount < 150) @include('admin.field-selection._add-row') @endif</tbody></table></div></section>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><i class="ri-history-line"></i> نسخه‌های انتخاب رشته</div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>نسخه</th><th>وضعیت</th><th>نمایش برای دانش‌آموز</th><th>ایجادکننده</th><th>تاریخ انتشار</th><th></th></tr></thead><tbody>@foreach($versions as $version)<tr><td>{{ \App\Support\PersianDate::number($version->version) }}</td><td><span class="badge text-bg-{{ $version->status === 'published' ? 'success' : ($version->status === 'archived' ? 'secondary' : 'warning') }}">{{ $version->status === 'published' ? 'منتشر شده' : ($version->status === 'archived' ? 'آرشیو شده' : 'پیش‌نویس') }}</span></td><td>@if($version->canBePubliclyVisible())<div class="d-flex flex-wrap align-items-center gap-2"><span class="badge text-bg-{{ $version->is_public_visible ? 'info' : 'light' }}">{{ $version->is_public_visible ? 'قابل مشاهده برای دانش‌آموز' : 'مخفی از دانش‌آموز' }}</span>@can('manage_field_selection')<form method="post" action="{{ route($version->is_public_visible ? 'admin.field-selection-plans.hide-from-student' : 'admin.field-selection-plans.show-to-student', $version) }}">@csrf<button class="btn btn-sm btn-outline-{{ $version->is_public_visible ? 'danger' : 'success' }}" onclick="return confirm('{{ $version->is_public_visible ? 'آیا این نسخه از دید دانش‌آموز مخفی شود؟' : 'آیا این نسخه برای دانش‌آموز قابل مشاهده شود؟' }}')">{{ $version->is_public_visible ? 'مخفی کردن از دانش‌آموز' : 'نمایش به دانش‌آموز' }}</button></form>@endcan</div>@else<span class="text-muted">فقط بعد از انتشار</span>@endif</td><td>{{ $version->creator?->name ?: '-' }}</td><td>{{ \App\Support\PersianDate::dateTime($version->published_at) }}</td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reservations.field-selection.show', [$reservation, 'plan' => $version]) }}" title="مشاهده"><i class="ri-eye-line"></i></a> <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('admin.field-selection-plans.print', $version) }}" title="چاپ"><i class="ri-printer-line"></i></a></td></tr>@endforeach</tbody></table></div>
    </div>
@endsection

@push('scripts')
@if($editable)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lists = [...document.querySelectorAll('.selection-list')], csrf = document.querySelector('input[name="_token"]')?.value;
    const formatter = new Intl.NumberFormat('fa-IR'), dirty = document.getElementById('selection-dirty'), toast = document.createElement('div');
    const catalogUrl = @json(route('admin.field-selection.search-fields')),
        catalogCitiesUrl = @json(route('admin.field-selection.filter-options.cities')),
        catalogAddUrl = @json(route('admin.field-selection-plans.items.from-catalog', $plan));
    let dragged, menu, suggestionTimer, catalogTimer, toastTimer, catalogAbort, cityAbort;
    toast.className = 'selection-toast'; document.body.append(toast);
    const headers = {'Accept':'application/json', 'X-CSRF-TOKEN':csrf};
    function itemRows() { return lists.flatMap(list => [...list.querySelectorAll('[data-item-row]')]); }
    function setCount(count = itemRows().length) { const hasItems = count > 0; document.getElementById('item-counter').textContent = formatter.format(count); document.querySelectorAll('[data-catalog-add]').forEach(button => button.disabled = count >= 150); document.querySelectorAll('[data-add-row] input, [data-add-row] textarea, [data-add-row] button').forEach(control => control.disabled = count >= 150); ['save-order', 'save-changes', 'publish-selection'].forEach(id => { const button = document.getElementById(id); if (!button) return; button.disabled = !hasItems; button.title = !hasItems && id === 'publish-selection' ? 'برای انتشار حداقل یک رشته ثبت کنید.' : ''; }); }
    function showToast(message, tone = 'success') { clearTimeout(toastTimer); toast.textContent = message; toast.className = 'selection-toast show ' + tone; toastTimer = setTimeout(() => toast.className = 'selection-toast', 3600); }
    async function jsonRequest(url, options = {}) { const response = await fetch(url, {...options, headers:{...headers, ...(options.headers || {})}}); const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || 'عملیات انجام نشد.'); return payload; }
    function setDirty() { dirty?.classList.add('is-visible'); }
    function clearDirty() { dirty?.classList.remove('is-visible'); document.querySelectorAll('.selection-row.is-changed').forEach(row => row.classList.remove('is-changed')); }
    function updateNumbers() { itemRows().forEach((row, index) => row.querySelector('[data-order]').textContent = formatter.format(index + 1)); setCount(); }
    function placeAddRow() { const row = document.querySelector('[data-add-row]'); if (!row) return; (itemRows().length < 75 ? lists[0] : lists[1]).append(row); }
    function rebalance() { itemRows().forEach((row, index) => (index < 75 ? lists[0] : lists[1]).append(row)); placeAddRow(); updateNumbers(); }
    function updateDuplicates() { const seen = new Map(); itemRows().forEach(row => { const input = row.querySelector('[data-field="field_code"]'); input.classList.remove('duplicate-code'); input.title = ''; const code = input.value.trim(); if (!code) return; const inputs = seen.get(code) || []; inputs.push(input); seen.set(code, inputs); }); seen.forEach(inputs => inputs.length > 1 && inputs.forEach(input => { input.classList.add('duplicate-code'); input.title = 'این کد رشته قبلاً در لیست ثبت شده است.'; })); }
    function markChanged(row) { if (!row?.matches('[data-item-row]')) { updateDuplicates(); return; } row.classList.add('is-changed'); setDirty(); updateDuplicates(); }
    function closeMenu() { menu?.remove(); menu = null; }
    function fillSuggestion(input, field) { const row = input.closest('tr'); row.querySelector('[data-field="field_code"]').value = field.field_code; row.querySelector('[data-field="field_name"]').value = field.field_name; row.querySelector('[data-field="city"]').value = field.city; const university = row.querySelector('[data-field="university_name"]'); if (university) university.value = field.university_name || field.institution || ''; const type = row.querySelector('[data-field="university_type"]'); if (type) type.value = field.university_type || field.course_type || ''; const desc = row.querySelector('[data-field="field_description"]'); if (desc) desc.value = field.field_description || field.university_description || ''; markChanged(row); closeMenu(); }
    async function searchCatalog(input) { const q = input.value.trim(); closeMenu(); if (q.length < 2) return; try { const fields = await jsonRequest(catalogUrl + '?q=' + encodeURIComponent(q)); if (!fields.length) return; const rect = input.getBoundingClientRect(); menu = document.createElement('div'); menu.className = 'catalog-menu'; menu.style.top = (window.scrollY + rect.bottom + 4) + 'px'; menu.style.left = Math.max(12, window.scrollX + rect.left) + 'px'; fields.slice(0, 8).forEach(field => { const button = document.createElement('button'); button.type = 'button'; button.className = 'catalog-option'; button.textContent = field.field_name + ' | ' + field.field_code + ' | ' + field.city; const small = document.createElement('small'); small.textContent = [field.booklet_source, field.university_name || field.institution, field.province, field.university_type || field.course_type, field.field_description || field.university_description].filter(Boolean).join(' / '); button.append(small); button.addEventListener('click', () => fillSuggestion(input, field)); menu.append(button); }); document.body.append(menu); } catch (_) {} }
    function bindRow(row) { const handle = row.querySelector('.drag-handle'); handle?.addEventListener('pointerdown', () => row.dataset.dragReady = '1'); row.addEventListener('dragstart', event => { if (!row.dataset.dragReady) { event.preventDefault(); return; } dragged = row; row.classList.add('dragging'); }); row.addEventListener('dragend', () => { row.classList.remove('dragging'); row.dataset.dragReady = ''; lists.forEach(list => list.classList.remove('is-over')); rebalance(); }); }
    function appendItem(payload) { const hadDirty = hasDirtyChanges(); const template = document.createElement('template'); template.innerHTML = payload.html.trim(); const row = template.content.firstElementChild; const target = itemRows().length < 75 ? lists[0] : lists[1]; target.insertBefore(row, target.querySelector('[data-add-row]')); document.querySelector('.empty-selection')?.remove(); bindRow(row); rebalance(); if (!hadDirty) clearDirty(); showToast(payload.message); }
    function hasDirtyChanges() { return dirty?.classList.contains('is-visible'); }
    async function saveBulkChanges() { const form = document.getElementById('bulk-update-form'); return jsonRequest(form.action, {method:'POST', body:new FormData(form)}); }
    async function saveOrderChanges() { const form = document.getElementById('reorder-form'); form.querySelectorAll('input[name="ordered_item_ids[]"]').forEach(input => input.remove()); itemRows().forEach(row => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ordered_item_ids[]'; input.value = row.dataset.itemId; form.append(input); }); return jsonRequest(form.action, {method:'POST', body:new FormData(form)}); }
    function catalogParams() { const params = new URLSearchParams(); const q = document.getElementById('catalog-field-query')?.value.trim() || ''; const province = document.getElementById('catalog-province')?.value || ''; const city = document.getElementById('catalog-city')?.value || ''; const course = document.getElementById('catalog-course-type')?.value || ''; if (q) params.set('q', q); if (province) params.set('province_id', province); if (city) params.set('city_id', city); if (course) params.set('course_type_id', course); return params; }
    function canSearchCatalog() { const params = catalogParams(); const q = params.get('q') || ''; return q.length >= 2 || params.has('province_id') || params.has('city_id') || params.has('course_type_id'); }
    function setCatalogState(message, tone = '') { document.getElementById('catalog-results').innerHTML = '<div class="catalog-state ' + tone + '">' + message + '</div>'; }
    async function loadCatalogCities(keepSelected = false) { const province = document.getElementById('catalog-province'), city = document.getElementById('catalog-city'); if (!province || !city) return; const selected = keepSelected ? city.value : ''; city.innerHTML = '<option value="">' + (province.value ? 'همه شهرهای استان' : 'اول استان را انتخاب کنید') + '</option>'; city.disabled = !province.value; if (!province.value) return; cityAbort?.abort(); cityAbort = new AbortController(); const params = catalogParams(); params.set('province_id', province.value); params.delete('city_id'); try { const response = await fetch(catalogCitiesUrl + '?' + params.toString(), {headers, signal: cityAbort.signal}); const cities = await response.json(); cities.forEach(item => city.add(new Option(item.name, item.id, false, String(item.id) === selected))); city.disabled = false; if (selected && [...city.options].some(option => option.value === selected)) city.value = selected; } catch (error) { if (error.name !== 'AbortError') city.disabled = false; } }
    function renderCatalog(fields) { const results = document.getElementById('catalog-results'); results.replaceChildren(); if (!fields.length) { setCatalogState('موردی با این فیلترها پیدا نشد.'); return; } const summary = document.createElement('div'); summary.className = 'catalog-state'; summary.textContent = fields.length >= 30 ? '۳۰ مورد اول نمایش داده شده؛ برای نتیجه دقیق‌تر نام رشته یا شهر را محدودتر کن.' : formatter.format(fields.length) + ' رشتهمحل پیدا شد.'; results.append(summary); fields.forEach(field => { const row = document.createElement('div'); row.className = 'catalog-result'; row.dataset.catalogResult = '1'; row.dataset.code = field.field_code; const code = document.createElement('span'); code.className = 'catalog-code ltr'; code.textContent = field.field_code; const main = document.createElement('div'); const title = document.createElement('strong'); title.className = 'catalog-title'; title.textContent = field.field_name; const meta = document.createElement('div'); meta.className = 'catalog-meta'; [field.booklet_source, field.university_type || field.course_type, field.capacity ? formatter.format(field.capacity) + ' نفر ظرفیت' : null].filter(Boolean).forEach(text => { const chip = document.createElement('span'); chip.className = 'catalog-chip'; chip.textContent = text; meta.append(chip); }); main.append(title, meta); const place = document.createElement('div'); place.className = 'catalog-result-place'; const inst = document.createElement('div'); inst.textContent = field.university_name || field.institution || '-'; const city = document.createElement('div'); city.className = 'catalog-place'; city.textContent = [field.province, field.city].filter(Boolean).join(' / '); place.append(inst, city); const desc = document.createElement('div'); desc.className = 'catalog-meta'; desc.textContent = field.field_description || field.university_description || 'بدون توضیحات ثبت‌شده'; const action = document.createElement('div'); action.className = 'catalog-result-action'; const add = document.createElement('button'); add.type = 'button'; add.className = 'btn btn-sm btn-primary'; add.dataset.catalogAdd = field.id; add.textContent = 'افزودن به لیست'; add.disabled = itemRows().length >= 150; action.append(add); row.append(code, main, place, desc, action); results.append(row); }); }
    async function runCatalogSearch() { clearTimeout(catalogTimer); if (!canSearchCatalog()) { setCatalogState('برای شروع، حداقل دو حرف از نام رشته را وارد کن یا یکی از فیلترها را انتخاب کن.'); return; } catalogAbort?.abort(); catalogAbort = new AbortController(); setCatalogState('در حال جستجو...', 'loading'); try { const fields = await jsonRequest(catalogUrl + '?' + catalogParams().toString(), {signal: catalogAbort.signal}); renderCatalog(fields); } catch (error) { if (error.name !== 'AbortError') setCatalogState(error.message); } }
    function scheduleCatalogSearch() { clearTimeout(catalogTimer); catalogTimer = setTimeout(runCatalogSearch, 300); }
    document.addEventListener('input', event => { const input = event.target; if (input.matches('[data-catalog-input]')) { markChanged(input.closest('tr')); clearTimeout(suggestionTimer); suggestionTimer = setTimeout(() => searchCatalog(input), 250); return; } if (input.matches('[data-item-input]')) markChanged(input.closest('[data-item-row]')); });
    document.addEventListener('click', event => { if (!event.target.closest('.catalog-menu') && !event.target.matches('[data-catalog-input]')) closeMenu(); });
    document.getElementById('selection-filter')?.addEventListener('input', function () { const needle = this.value.trim().toLowerCase(); itemRows().forEach(row => { const values = [...row.querySelectorAll('input, textarea')].map(input => input.value).join(' '); row.hidden = needle && !(row.textContent + ' ' + values).toLowerCase().includes(needle); }); });
    document.getElementById('add-first-row')?.addEventListener('click', () => document.querySelector('[data-add-row] [data-field="field_code"]')?.focus());
    document.getElementById('catalog-field-query')?.addEventListener('input', () => { loadCatalogCities(false); scheduleCatalogSearch(); });
    document.getElementById('catalog-province')?.addEventListener('change', () => { loadCatalogCities(false); runCatalogSearch(); });
    document.getElementById('catalog-city')?.addEventListener('change', runCatalogSearch);
    document.getElementById('catalog-course-type')?.addEventListener('change', () => { loadCatalogCities(true); runCatalogSearch(); });
    document.getElementById('catalog-search-button')?.addEventListener('click', runCatalogSearch);
    document.getElementById('catalog-reset-button')?.addEventListener('click', () => { document.getElementById('catalog-field-query').value = ''; document.getElementById('catalog-province').value = ''; document.getElementById('catalog-course-type').value = ''; loadCatalogCities(false); setCatalogState('برای شروع، حداقل نام رشته را وارد کن یا یکی از فیلترها را انتخاب کن.'); });
    document.getElementById('catalog-results')?.addEventListener('click', async event => { const button = event.target.closest('[data-catalog-add]'); if (!button || button.disabled) return; const result = button.closest('[data-catalog-result]'); const code = result?.dataset.code || ''; if (itemRows().some(row => row.querySelector('[data-field="field_code"]').value.trim() === code)) { showToast('این کد رشته قبلاً در لیست ثبت شده است.', 'warning'); return; } button.disabled = true; try { appendItem(await jsonRequest(catalogAddUrl, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({field_catalog_id:button.dataset.catalogAdd})})); } catch (error) { button.disabled = false; showToast(error.message, 'danger'); } });
    document.getElementById('add-item-form')?.addEventListener('submit', async function (event) { event.preventDefault(); const code = this.querySelector('[name="field_code"]')?.value.trim(); if (code && itemRows().some(row => row.querySelector('[data-field="field_code"]').value.trim() === code)) { showToast('این کد رشته قبلاً در لیست ثبت شده است.', 'warning'); return; } try { appendItem(await jsonRequest(this.action, {method:'POST', body:new FormData(this)})); this.reset(); } catch (error) { showToast(error.message, 'danger'); } });
    document.getElementById('bulk-update-form')?.addEventListener('submit', async function (event) { event.preventDefault(); try { const payload = await saveBulkChanges(); await saveOrderChanges(); clearDirty(); showToast(payload.message); } catch (error) { showToast(error.message, 'danger'); } });
    document.getElementById('reorder-form')?.addEventListener('submit', async function (event) { event.preventDefault(); try { const payload = await saveOrderChanges(); clearDirty(); showToast(payload.message); } catch (error) { showToast(error.message, 'danger'); } });
    document.getElementById('publish-form')?.addEventListener('submit', async function (event) { event.preventDefault(); if (!itemRows().length) { showToast('برای انتشار حداقل یک رشته ثبت کنید.', 'warning'); return; } if (!confirm(this.dataset.confirm || 'آیا از انتشار این لیست انتخاب رشته مطمئن هستید؟')) return; const button = document.getElementById('publish-selection'); if (button) button.disabled = true; try { if (hasDirtyChanges()) { await saveBulkChanges(); await saveOrderChanges(); clearDirty(); } this.submit(); } catch (error) { if (button) button.disabled = false; showToast(error.message, 'danger'); } });
    document.addEventListener('click', async event => { const button = event.target.closest('[data-delete-item]'); if (!button || !confirm('این رشته حذف شود؟')) return; try { const payload = await jsonRequest(button.dataset.deleteUrl, {method:'POST', body:new URLSearchParams({_method:'DELETE', _token:csrf})}); button.closest('[data-item-row]').remove(); rebalance(); clearDirty(); showToast(payload.message); } catch (error) { showToast(error.message, 'danger'); } });
    lists.forEach(list => { list.addEventListener('dragover', event => { event.preventDefault(); list.classList.add('is-over'); }); list.addEventListener('dragleave', () => list.classList.remove('is-over')); list.addEventListener('drop', event => { event.preventDefault(); if (!dragged) return; const before = [...list.querySelectorAll('[data-item-row]')].find(row => event.clientY < row.getBoundingClientRect().top + row.offsetHeight / 2); list.insertBefore(dragged, before || list.querySelector('[data-add-row]')); setDirty(); }); });
    itemRows().forEach(bindRow); rebalance(); updateDuplicates();
});
</script>
@endif
@endpush

@extends('layouts.admin')

@section('title', 'مدیریت انتخاب رشته')

@push('styles')
<style>
    .selection-toolbar{position:sticky;top:1rem;z-index:10}.selection-summary{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}.selection-counter{font-size:.82rem;color:var(--ink-500)}.selection-dirty{display:none;font-size:.78rem;color:var(--warning);font-weight:600}.selection-dirty.is-visible{display:inline-flex;align-items:center;gap:.25rem}.selection-search{max-width:370px}.selection-columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.selection-column{border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;background:var(--surface)}.selection-column-head{padding:.8rem 1rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.88rem;font-weight:700}.selection-table{width:100%;min-width:1080px;border-collapse:collapse}.selection-table th{font-size:.74rem;color:var(--ink-500);font-weight:600;background:var(--surface);white-space:nowrap}.selection-table th,.selection-table td{padding:.5rem;border-bottom:1px solid var(--border);vertical-align:middle}.selection-row{transition:background-color .15s ease,box-shadow .15s ease}.selection-row:hover{background:var(--brand-50)}.selection-row.is-changed{background:#fff8e7;box-shadow:inset 3px 0 0 var(--warning)}.selection-row.dragging{opacity:.42}.selection-list.is-over{background:var(--brand-50)}.selection-number{width:2rem;height:2rem;border-radius:50%;background:var(--brand-50);color:var(--brand-700);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem}.drag-handle{border:0;background:transparent;color:var(--ink-500);cursor:grab;padding:.3rem}.drag-handle:active{cursor:grabbing}.selection-input{min-width:100px;padding:.45rem .55rem;min-height:36px;font-size:.82rem}.selection-textarea{min-width:210px;resize:vertical;line-height:1.7}.selection-actions{display:flex;gap:.25rem;white-space:nowrap}.catalog-menu{position:absolute;z-index:1000;width:min(520px,calc(100vw - 2rem));background:var(--surface);border:1px solid var(--border);box-shadow:var(--shadow-md);border-radius:var(--radius-sm);overflow:hidden}.catalog-option{display:block;width:100%;border:0;border-bottom:1px solid var(--border);background:var(--surface);text-align:right;padding:.65rem .8rem;font-size:.8rem}.catalog-option:hover{background:var(--brand-50)}.catalog-option:last-child{border-bottom:0}.catalog-option small{display:block;color:var(--ink-500);margin-top:.15rem}.catalog-filter-grid{display:grid;grid-template-columns:minmax(220px,1.4fr) repeat(3,minmax(160px,1fr)) auto;gap:.75rem;align-items:end}.catalog-help{font-size:.8rem;color:var(--ink-500);margin-top:.7rem}.catalog-results{max-height:390px;overflow:auto;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface)}.catalog-result{display:grid;grid-template-columns:96px minmax(230px,1fr) minmax(210px,.9fr) minmax(220px,1fr) 112px;gap:.75rem;align-items:center;padding:.8rem .9rem;border-bottom:1px solid var(--border);font-size:.85rem}.catalog-result:last-child{border-bottom:0}.catalog-result:hover{background:var(--brand-50)}.catalog-code{font-weight:700;color:var(--brand-700)}.catalog-title{display:block;margin-bottom:.25rem}.catalog-meta,.catalog-place{display:flex;flex-wrap:wrap;gap:.25rem .5rem;color:var(--ink-500);font-size:.78rem}.catalog-chip{display:inline-flex;align-items:center;gap:.2rem;border:1px solid var(--border);border-radius:999px;padding:.12rem .45rem;background:var(--bg);white-space:nowrap}.catalog-state{padding:1rem;color:var(--ink-500);font-size:.84rem}.catalog-state.loading{color:var(--brand-700)}.add-row td{background:#f8fbff}.duplicate-code{border-color:var(--warning)!important;background:#fff8e7}.empty-selection{padding:2rem 1rem;text-align:center;color:var(--ink-500)}.selection-toast{position:fixed;left:1rem;bottom:1rem;z-index:1100;display:none;max-width:340px;padding:.8rem 1rem;border-radius:var(--radius-sm);color:#fff;box-shadow:var(--shadow-md);font-size:.85rem}.selection-toast.show{display:block}.selection-toast.success{background:var(--success)}.selection-toast.warning{background:var(--warning)}.selection-toast.danger{background:var(--danger)}@media(max-width:1199.98px){.catalog-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.catalog-filter-actions{grid-column:1 / -1}.catalog-result{grid-template-columns:90px 1fr auto}.catalog-result-place{grid-column:2 / 3}.catalog-result-action{grid-row:1 / span 2;grid-column:3}}@media(max-width:991.98px){.selection-toolbar{position:static}.selection-columns{grid-template-columns:1fr}}@media(max-width:575.98px){.selection-search{max-width:none;width:100%}.selection-toolbar .btn{flex:1}.selection-table{min-width:1080px}.catalog-filter-grid{grid-template-columns:1fr}.catalog-result{grid-template-columns:1fr}.catalog-result-place,.catalog-result-action{grid-column:auto;grid-row:auto}.catalog-result-action .btn{width:100%}}
</style>
@endpush

@push('styles')
<style>
    /* ===========================================================
       Row reordering — drag handle, step buttons, jump-to-priority
    =========================================================== */
    .row-move {
        display: flex;
        align-items: center;
        gap: .1rem;
    }

    /* without this the browser scrolls the page instead of starting a
       touch drag when the finger goes down on the handle */
    .drag-handle {
        touch-action: none;
        -webkit-user-select: none;
        user-select: none;
    }

    .row-move__steps {
        display: inline-flex;
        flex-direction: column;
    }

    .row-move__btn {
        width: 22px;
        height: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 0;
        border-radius: 5px;
        background: transparent;
        color: var(--ink-500);
        font-size: .9rem;
        line-height: 1;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }

    .row-move__btn:hover:not(:disabled) { background: var(--brand-50); color: var(--brand-700); }
    .row-move__btn:disabled { opacity: .3; cursor: default; }

    .selection-number--button {
        border: 0;
        font-family: inherit;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }

    .selection-number--button:hover {
        background: var(--brand-500);
        color: #fff;
    }

    .selection-jump {
        width: 3.6rem;
        height: 2rem;
        padding: 0 .3rem;
        border: 1px solid var(--brand-500);
        border-radius: var(--radius-sm);
        font-size: .8rem;
        font-weight: 700;
        text-align: center;
        color: var(--ink-900);
        background: #fff;
        outline: none;
        box-shadow: 0 0 0 .2rem rgba(47, 143, 131, .12);
    }

    /* touch drag feedback */
    .selection-row.is-touch-dragging {
        background: var(--brand-50) !important;
        box-shadow: inset 3px 0 0 var(--brand-500), var(--shadow-md);
        opacity: .9;
    }

    @media (hover: none) {
        /* fingers need a bigger target than a mouse pointer */
        .drag-handle { padding: .5rem .35rem; font-size: 1.15rem; }
        .row-move__btn { width: 26px; height: 22px; font-size: 1.05rem; }
    }

    /* ===========================================================
       Toolbar — identity row + action row
    =========================================================== */
    /* .card sets overflow:hidden globally, which would clip the actions menu */
    .selection-toolbar { overflow: visible; }
    .selection-toolbar .card-body { padding: .9rem 1.1rem; }

    .toolbar-identity {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .toolbar-back { flex: 0 0 auto; }

    .toolbar-title {
        flex: 1 1 240px;
        min-width: 0;
    }

    .toolbar-title > strong {
        display: block;
        font-size: .98rem;
        line-height: 1.7;
        color: var(--ink-900);
    }

    .toolbar-badges {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .4rem;
        margin-top: .25rem;
    }

    .toolbar-badges .badge { font-weight: 600; }
    .toolbar-badges .selection-counter { margin-inline-end: .2rem; }

    .toolbar-exam-switch {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: .4rem;
        margin: 0;
    }

    .toolbar-exam-switch__label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-500);
        margin: 0;
    }

    .toolbar-exam-switch .form-select {
        width: auto;
        min-width: 148px;
        height: 38px;
        font-size: .84rem;
    }

    .toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        margin-top: .85rem;
        padding-top: .85rem;
        border-top: 1px solid var(--border);
    }

    .toolbar-actions .btn { height: 38px; }

    /* the publish form keeps its checkbox beside its button */
    #publish-form {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 0;
    }

    #publish-form .form-check { margin: 0; }
    #publish-form .form-check-label { font-size: .78rem; color: var(--ink-700); }

    /* ---- "more actions" menu — always parked at the far end ---- */
    .toolbar-menu {
        position: relative;
        margin-inline-start: auto;
    }

    .toolbar-menu__panel {
        position: absolute;
        z-index: 40;
        top: calc(100% + 6px);
        /* the toggle sits at the inline-end of the row, so the panel has to
           grow back toward the inline-start or it lands off-screen */
        inset-inline-end: 0;
        min-width: 232px;
        padding: .3rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow-md);
    }

    .toolbar-menu__panel form { margin: 0; }

    .toolbar-menu__item {
        display: flex;
        align-items: center;
        gap: .5rem;
        width: 100%;
        padding: .5rem .6rem;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--ink-700);
        font-family: inherit;
        font-size: .83rem;
        text-align: right;
        text-decoration: none;
        cursor: pointer;
    }

    .toolbar-menu__item i { font-size: 1rem; color: var(--ink-500); }
    .toolbar-menu__item:hover { background: var(--brand-50); color: var(--brand-700); }
    .toolbar-menu__item:hover i { color: inherit; }
    .toolbar-menu__item.is-danger:hover { background: #fcecea; color: var(--danger); }
    .toolbar-menu__item.is-success:hover { background: #eaf7f1; color: var(--success); }

    .toolbar-menu__divider {
        height: 1px;
        margin: .25rem .3rem;
        background: var(--border);
    }

    @media (max-width: 767.98px) {
        .toolbar-exam-switch { flex: 1 1 100%; }
        .toolbar-exam-switch .form-select { flex: 1 1 auto; }
        #publish-form { flex: 1 1 100%; flex-wrap: wrap; }
        .toolbar-actions .btn { flex: 1 1 auto; }
        .toolbar-menu { flex: 1 1 100%; margin-inline-start: 0; }
        .toolbar-menu__toggle { width: 100%; }
        .toolbar-menu__panel { inset-inline: 0; }
    }

    /* ===========================================================
       Catalog filter panel — layout polish
    =========================================================== */
    .catalog-filter-grid {
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: .85rem 1rem;
        align-items: start;
    }

    .catalog-filter-grid > div { min-width: 0; }

    .catalog-filter-grid .form-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-700);
        margin-bottom: .35rem;
    }

    .filter-note {
        font-size: .7rem;
        font-weight: 400;
        color: var(--ink-500);
    }

    .catalog-filter-grid .form-select,
    .catalog-filter-grid .form-control {
        height: 40px;
    }

    .catalog-filter-actions {
        grid-column: 1 / -1;
        justify-content: flex-end;
        padding-top: .2rem;
    }

    .catalog-filter-actions .btn { height: 40px; }

    /* the "خوابگاه" note should not sit in a filter column */
    .catalog-filter-grid > .catalog-help { grid-column: 1 / -1; margin-top: 0; }

    /* ===========================================================
       Multi-select (provinces)
    =========================================================== */
    .catalog-provinces-field { grid-column: span 2; }

    .ms-field { position: relative; }

    .ms-native {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .ms-control {
        width: 100%;
        height: 40px;
        display: flex;
        align-items: center;
        gap: .45rem;
        padding: 0 .7rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--ink-900);
        font-size: .85rem;
        text-align: right;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .ms-control:hover:not(:disabled) { border-color: var(--brand-200); }

    .ms-control:focus-visible,
    .ms-field.is-open .ms-control {
        outline: none;
        border-color: var(--brand-500);
        box-shadow: 0 0 0 .2rem rgba(47, 143, 131, .12);
    }

    .ms-control:disabled { background: var(--ink-100); cursor: not-allowed; opacity: .7; }

    .ms-control__icon { color: var(--ink-500); font-size: 1rem; }

    .ms-control__label {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ms-control__label.is-placeholder { color: var(--ink-500); }

    .ms-control__count {
        flex: 0 0 auto;
        min-width: 1.4rem;
        padding: .05rem .4rem;
        border-radius: 999px;
        background: var(--brand-500);
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        text-align: center;
    }

    .ms-control__caret { color: var(--ink-500); transition: transform .18s ease; }
    .ms-field.is-open .ms-control__caret { transform: rotate(180deg); }

    .ms-panel {
        position: absolute;
        z-index: 50;
        top: calc(100% + 4px);
        inset-inline: 0;
        min-width: 240px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }

    .ms-panel__search {
        display: flex;
        align-items: center;
        gap: .4rem;
        padding: .55rem .7rem;
        border-bottom: 1px solid var(--border);
        color: var(--ink-500);
    }

    .ms-panel__search input {
        flex: 1 1 auto;
        min-width: 0;
        border: 0;
        outline: none;
        background: transparent;
        font-family: inherit;
        font-size: .83rem;
        color: var(--ink-900);
    }

    .ms-panel__tools {
        display: flex;
        gap: .4rem;
        padding: .45rem .7rem;
        border-bottom: 1px solid var(--border);
        background: var(--bg);
    }

    .ms-tool {
        border: 1px solid var(--border);
        border-radius: 999px;
        background: var(--surface);
        color: var(--ink-700);
        padding: .18rem .6rem;
        font-family: inherit;
        font-size: .74rem;
        cursor: pointer;
        transition: border-color .15s ease, color .15s ease, background .15s ease;
    }

    .ms-tool:hover { border-color: var(--brand-500); color: var(--brand-700); background: var(--brand-50); }

    .ms-panel__options { max-height: 230px; overflow-y: auto; padding: .3rem; }

    .ms-option {
        display: flex;
        align-items: center;
        gap: .5rem;
        width: 100%;
        padding: .42rem .55rem;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--ink-900);
        font-family: inherit;
        font-size: .83rem;
        text-align: right;
        cursor: pointer;
    }

    .ms-option:hover { background: var(--brand-50); }

    .ms-option__box {
        flex: 0 0 auto;
        width: 17px;
        height: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--ink-300);
        border-radius: 5px;
        background: var(--surface);
        color: #fff;
        font-size: .7rem;
        transition: background .15s ease, border-color .15s ease;
    }

    .ms-option__box i { opacity: 0; }

    .ms-option.is-selected .ms-option__box {
        background: var(--brand-500);
        border-color: var(--brand-500);
    }

    .ms-option.is-selected .ms-option__box i { opacity: 1; }
    .ms-option.is-selected { color: var(--brand-700); font-weight: 600; }

    .ms-panel__empty { padding: .9rem .7rem; color: var(--ink-500); font-size: .8rem; text-align: center; }

    .ms-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-top: .45rem;
    }

    .ms-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .18rem .3rem .18rem .55rem;
        border: 1px solid var(--brand-200);
        border-radius: 999px;
        background: var(--brand-50);
        color: var(--brand-700);
        font-size: .74rem;
        font-weight: 600;
    }

    .ms-chip__remove {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border: 0;
        border-radius: 50%;
        background: rgba(47, 143, 131, .14);
        color: var(--brand-700);
        font-size: .7rem;
        cursor: pointer;
        padding: 0;
    }

    .ms-chip__remove:hover { background: var(--brand-500); color: #fff; }

    .ms-chips__clear {
        border: 0;
        background: transparent;
        color: var(--ink-500);
        font-family: inherit;
        font-size: .73rem;
        text-decoration: underline;
        cursor: pointer;
        padding: .18rem .2rem;
    }

    .ms-chips__clear:hover { color: var(--danger); }

    @media (max-width: 1199.98px) {
        .catalog-provinces-field { grid-column: span 2; }
    }

    @media (max-width: 575.98px) {
        .catalog-provinces-field { grid-column: 1 / -1; }
        .catalog-filter-actions { justify-content: stretch; }
        .catalog-filter-actions .btn { flex: 1; }
    }
</style>
@endpush

@section('content')
    @php($editable = $plan->status !== \App\Models\FieldSelectionPlan::STATUS_ARCHIVED && auth()->user()->can('manage_field_selection'))
    @php($itemsCount = $plan->items->count())
    <form id="bulk-update-form" method="post" action="{{ route('admin.field-selection-plans.bulk-update', $plan) }}">@csrf</form>
    <form id="add-item-form" method="post" action="{{ route('admin.field-selection-plans.items.store', $plan) }}">@csrf</form>
    <form id="reorder-form" method="post" action="{{ route('admin.field-selection-plans.reorder', $plan) }}">@csrf</form>

    <div class="card selection-toolbar mb-3">
        <div class="card-body">

            {{-- Row 1 — identity: who/what this plan is --}}
            <div class="toolbar-identity">
                <a class="btn btn-outline-secondary toolbar-back" href="{{ route('admin.reservations.show', $reservation) }}" title="بازگشت به رزرو">
                    <i class="ri-arrow-right-line"></i><span class="d-none d-sm-inline">بازگشت</span>
                </a>

                <div class="toolbar-title">
                    <strong>انتخاب رشته {{ $selectedExamTypeLabel }}</strong>
                    <div class="toolbar-badges">
                        <span class="selection-counter"><span id="item-counter">{{ \App\Support\PersianDate::number($itemsCount) }}</span> از ۱۵۰ مورد</span>
                        <span class="badge text-bg-{{ $plan->isPublished() ? 'success' : ($plan->status === \App\Models\FieldSelectionPlan::STATUS_ARCHIVED ? 'secondary' : 'warning') }}">{{ $plan->status === 'published' ? 'منتشر شده' : ($plan->status === 'archived' ? 'آرشیو شده' : 'پیش‌نویس') }}</span>
                        @if($plan->canBePubliclyVisible())<span class="badge text-bg-{{ $plan->is_public_visible ? 'info' : 'light' }}">{{ $plan->is_public_visible ? 'قابل مشاهده برای دانش‌آموز' : 'مخفی از دانش‌آموز' }}</span>@endif
                        @if($editable)<span class="selection-dirty" id="selection-dirty"><i class="ri-error-warning-line"></i> تغییرات ذخیره نشده دارید</span>@endif
                    </div>
                </div>

                @can('manage_field_selection')
                    <form method="get" action="{{ route('admin.reservations.field-selection.show', $reservation) }}" class="toolbar-exam-switch">
                        <label class="toolbar-exam-switch__label" for="toolbar-exam-type">کنکور</label>
                        <select class="form-select" id="toolbar-exam-type" name="exam_type" onchange="this.form.submit()">
                            @foreach($examTypeOptions as $key => $label)<option value="{{ $key }}" @selected($selectedExamType === $key)>{{ $label }}</option>@endforeach
                        </select>
                    </form>
                @endcan
            </div>

            {{-- Row 2 — actions: primary on the right, the rest folded into a menu --}}
            <div class="toolbar-actions">
                @if($editable)
                    <button class="btn btn-outline-primary" type="submit" form="reorder-form" id="save-order" @disabled($itemsCount === 0)><i class="ri-list-check-2"></i> ذخیره ترتیب</button>
                    <button class="btn btn-primary" type="submit" form="bulk-update-form" id="save-changes" @disabled($itemsCount === 0)><i class="ri-save-line"></i> ذخیره تغییرات</button>
                    @if($plan->isDraft())<form method="post" action="{{ route('admin.field-selection-plans.publish', $plan) }}" id="publish-form" data-confirm="آیا از انتشار این لیست انتخاب رشته مطمئن هستید؟">@csrf<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="visible_to_student" value="1" id="visible-to-student"><label class="form-check-label small" for="visible-to-student">بعد از انتشار برای دانش‌آموز قابل مشاهده باشد</label></div><button class="btn btn-success" id="publish-selection" @disabled($itemsCount === 0) title="{{ $itemsCount === 0 ? 'برای انتشار حداقل یک رشته ثبت کنید.' : '' }}"><i class="ri-send-plane-line"></i> انتشار</button></form>@endif
                @endif

                {{-- Secondary + destructive actions live behind one menu so the
                     toolbar keeps only what is used on every visit. --}}
                <div class="toolbar-menu" data-toolbar-menu>
                    <button type="button" class="btn btn-outline-secondary toolbar-menu__toggle"
                            data-toolbar-menu-toggle aria-haspopup="true" aria-expanded="false">
                        <i class="ri-more-2-fill"></i>
                        <span class="d-none d-sm-inline">عملیات بیشتر</span>
                    </button>

                    <div class="toolbar-menu__panel" data-toolbar-menu-panel hidden>
                        <a class="toolbar-menu__item" target="_blank" href="{{ route('admin.field-selection-plans.print', $plan) }}">
                            <i class="ri-printer-line"></i> چاپ انتخاب رشته
                        </a>

                        @can('manage_field_selection')
                            @if($plan->canBePubliclyVisible())
                                <form method="post" action="{{ route($plan->is_public_visible ? 'admin.field-selection-plans.hide-from-student' : 'admin.field-selection-plans.show-to-student', $plan) }}">
                                    @csrf
                                    <button class="toolbar-menu__item {{ $plan->is_public_visible ? 'is-danger' : 'is-success' }}"
                                            onclick="return confirm('{{ $plan->is_public_visible ? 'آیا این نسخه از دید دانش‌آموز مخفی شود؟' : 'آیا این نسخه برای دانش‌آموز قابل مشاهده شود؟' }}')" type="submit">
                                        <i class="{{ $plan->is_public_visible ? 'ri-eye-off-line' : 'ri-eye-line' }}"></i>
                                        {{ $plan->is_public_visible ? 'مخفی کردن از دانش‌آموز' : 'نمایش به دانش‌آموز' }}
                                    </button>
                                </form>
                            @endif

                            @if($plan->status !== \App\Models\FieldSelectionPlan::STATUS_ARCHIVED)
                                <form method="post" action="{{ route('admin.field-selection-plans.archive', $plan) }}">
                                    @csrf
                                    <button class="toolbar-menu__item" onclick="return confirm('آیا از آرشیو کردن این نسخه مطمئن هستید؟')" type="submit">
                                        <i class="ri-archive-line"></i> آرشیو کردن
                                    </button>
                                </form>
                            @endif

                            <div class="toolbar-menu__divider"></div>

                            <form method="post" action="{{ route('admin.field-selection-plans.destroy', $plan) }}" onsubmit="return confirm('آیا از حذف این انتخاب رشته مطمئن هستید؟')">
                                @csrf @method('delete')
                                <button class="toolbar-menu__item is-danger" type="submit">
                                    <i class="ri-delete-bin-6-line"></i> حذف انتخاب رشته
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($plan->isPublished() && ! $plan->is_public_visible)
        <div class="alert alert-warning py-2">این نسخه منتشر شده است اما هنوز برای دانش‌آموز قابل مشاهده نیست.</div>
    @endif

    @if(false)<div class="d-flex flex-wrap gap-2 mb-3 small text-muted">
        <span>کل نسخه‌ها: {{ \App\Support\PersianDate::number($planVisibilityStats['total']) }}</span>
        <span>نسخه‌های منتشر شده: {{ \App\Support\PersianDate::number($planVisibilityStats['published']) }}</span>
        <span>نسخه‌های قابل مشاهده برای دانش‌آموز: {{ \App\Support\PersianDate::number($planVisibilityStats['student_visible']) }}</span>
    </div>@endif

    <div class="card mb-3">
        <div class="card-header"><i class="ri-search-line"></i>جستجوی رشته  </div>
        <div class="card-body">
            <div class="catalog-filter-grid">
                <div>
                    <label class="form-label" for="catalog-field-query">نام رشته، کدرشته یا توضیحات</label>
                    <input class="form-control" id="catalog-field-query" placeholder="مثلاً پرستاری" autocomplete="off" @disabled(! $editable)>
                </div>
                <div>
                    <label class="form-label" for="catalog-province">
                        استان
                        <span class="filter-note">(برای انتخاب شهر)</span>
                    </label>
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
                <div>
                    <label class="form-label" for="catalog-booklet">دفترچه</label>
                    <select class="form-select" id="catalog-booklet" @disabled(! $editable)><option value="">همه دفترچه‌ها</option>@foreach($catalogBooklets as $booklet)<option value="{{ $booklet }}">{{ basename($booklet) }}</option>@endforeach</select>
                </div>
                <div class="catalog-provinces-field">
                    <label class="form-label" for="catalog-provinces-toggle">
                        فیلتر چند استان
                        <span class="filter-note">(می‌توانید چند مورد را انتخاب کنید)</span>
                    </label>

                    {{-- The native <select multiple> stays in the DOM (hidden) because the
                         existing catalogParams()/reset code reads it directly. The widget
                         below only drives its `selected` flags and fires `change`. --}}
                    <div class="ms-field" data-multiselect>
                        <button type="button" class="ms-control" id="catalog-provinces-toggle"
                                data-ms-toggle aria-haspopup="listbox" aria-expanded="false"
                                @disabled(! $editable)>
                            <i class="ri-map-pin-line ms-control__icon"></i>
                            <span class="ms-control__label" data-ms-label>همه استان‌ها</span>
                            <span class="ms-control__count" data-ms-count hidden></span>
                            <i class="ri-arrow-down-s-line ms-control__caret"></i>
                        </button>

                        <div class="ms-panel" data-ms-panel hidden>
                            <div class="ms-panel__search">
                                <i class="ri-search-line"></i>
                                <input type="text" data-ms-search placeholder="جستجوی استان…" autocomplete="off">
                            </div>
                            <div class="ms-panel__tools">
                                <button type="button" class="ms-tool" data-ms-all>انتخاب همه</button>
                                <button type="button" class="ms-tool" data-ms-none>پاک کردن</button>
                            </div>
                            <div class="ms-panel__options" data-ms-options role="listbox" aria-multiselectable="true"></div>
                            <div class="ms-panel__empty" data-ms-empty hidden>استانی با این نام پیدا نشد.</div>
                        </div>

                        <select class="ms-native" id="catalog-provinces" multiple tabindex="-1" aria-hidden="true"
                                @disabled(! $editable)>@foreach($catalogProvinces as $province)<option value="{{ $province->id }}">{{ $province->name }}</option>@endforeach</select>
                    </div>

                    <div class="ms-chips" data-ms-chips hidden></div>
                </div>
                <div>
                    <label class="form-label" for="catalog-semester">نیم‌سال</label>
                    <select class="form-select" id="catalog-semester" @disabled(! $editable)><option value="">همه</option><option value="first">نیم‌سال اول</option><option value="second">نیم‌سال دوم</option></select>
                </div>
                <div>
                    <label class="form-label" for="catalog-academic-record">سوابق تحصیلی</label>
                    <select class="form-select" id="catalog-academic-record" @disabled(! $editable)><option value="">همه</option><option value="with_records">با سوابق تحصیلی</option><option value="without_records">بدون سوابق تحصیلی</option></select>
                </div>
                <div>
                    <label class="form-label" for="catalog-gender">جنسیت دانشگاه</label>
                    <select class="form-select" id="catalog-gender" @disabled(! $editable)><option value="">همه</option><option value="male">مرد</option><option value="female">زن</option></select>
                </div>
                <div class="catalog-help">خوابگاه: داده‌ای برای این فیلتر در کاتالوگ فعلی وارد نشده است.</div>
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

    @if(false)<div class="card mt-3">
        <div class="card-header"><i class="ri-history-line"></i> نسخه‌های انتخاب رشته</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>نسخه</th>
                        <th>وضعیت</th>
                        <th>نمایش برای دانش‌آموز</th>
                        <th>تاریخ ایجاد</th>
                        <th>تاریخ انتشار</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $version)
                        <tr>
                            <td>{{ \App\Support\PersianDate::number($version->version) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $version->status === 'published' ? 'success' : ($version->status === 'archived' ? 'secondary' : 'warning') }}">
                                    {{ $version->status === 'published' ? 'منتشر شده' : ($version->status === 'archived' ? 'آرشیو شده' : 'پیش‌نویس') }}
                                </span>
                            </td>
                            <td>
                                @if($version->isPublished())
                                    <span class="badge text-bg-{{ $version->is_public_visible ? 'info' : 'light' }}">
                                        {{ $version->is_public_visible ? 'قابل مشاهده برای دانش‌آموز' : 'مخفی از دانش‌آموز' }}
                                    </span>
                                @else
                                    <span class="text-muted">مخفی از دانش‌آموز</span>
                                @endif
                            </td>
                            <td>{{ \App\Support\PersianDate::dateTime($version->created_at) }}</td>
                            <td>{{ \App\Support\PersianDate::dateTime($version->published_at) }}</td>
                            <td class="text-nowrap">
                                <div class="d-flex flex-wrap gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reservations.field-selection.show', [$reservation, 'plan' => $version]) }}">مشاهده</a>
                                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('admin.field-selection-plans.print', $version) }}">چاپ</a>
                                    @can('manage_field_selection')
                                        @if($version->isDraft())
                                            <form method="post" action="{{ route('admin.field-selection-plans.publish', $version) }}">
                                                @csrf
                                                <input type="hidden" name="visible_to_student" value="0">
                                                <button class="btn btn-sm btn-success">انتشار</button>
                                            </form>
                                        @endif
                                        @if($version->isPublished())
                                            <form method="post" action="{{ route($version->is_public_visible ? 'admin.field-selection-plans.hide-from-student' : 'admin.field-selection-plans.show-to-student', $version) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-{{ $version->is_public_visible ? 'danger' : 'success' }}">{{ $version->is_public_visible ? 'مخفی کردن از دانش‌آموز' : 'نمایش به دانش‌آموز' }}</button>
                                            </form>
                                        @endif
                                        @if($version->status !== \App\Models\FieldSelectionPlan::STATUS_ARCHIVED)
                                            <form method="post" action="{{ route('admin.field-selection-plans.archive', $version) }}" onsubmit="return confirm('آیا از آرشیو کردن این نسخه مطمئن هستید؟')">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary">آرشیو کردن</button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>@endif
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
    function catalogParams() { const params = new URLSearchParams(); const q = document.getElementById('catalog-field-query')?.value.trim() || ''; const province = document.getElementById('catalog-province')?.value || ''; const city = document.getElementById('catalog-city')?.value || ''; const course = document.getElementById('catalog-course-type')?.value || ''; const booklet = document.getElementById('catalog-booklet')?.value || ''; const semester = document.getElementById('catalog-semester')?.value || ''; const records = document.getElementById('catalog-academic-record')?.value || ''; const gender = document.getElementById('catalog-gender')?.value || ''; const provinces = [...(document.getElementById('catalog-provinces')?.selectedOptions || [])].map(option => option.value); if (q) params.set('q', q); if (province) params.set('province_id', province); if (city) params.set('city_id', city); if (course) params.set('course_type_id', course); if (booklet) params.set('booklet', booklet); if (semester) params.set('semester', semester); if (records) params.set('academic_record_type', records); if (gender) params.set('gender', gender); provinces.forEach(id => params.append('province_ids[]', id)); return params; }
    function canSearchCatalog() { const params = catalogParams(); const q = params.get('q') || ''; return q.length >= 2 || [...params.keys()].some(key => key !== 'q'); }
    function setCatalogState(message, tone = '') { document.getElementById('catalog-results').innerHTML = '<div class="catalog-state ' + tone + '">' + message + '</div>'; }
    async function loadCatalogCities(keepSelected = false) { const province = document.getElementById('catalog-province'), city = document.getElementById('catalog-city'); if (!province || !city) return; const selected = keepSelected ? city.value : ''; city.innerHTML = '<option value="">' + (province.value ? 'همه شهرهای استان' : 'اول استان را انتخاب کنید') + '</option>'; city.disabled = !province.value; if (!province.value) return; cityAbort?.abort(); cityAbort = new AbortController(); const params = catalogParams(); params.set('province_id', province.value); params.delete('city_id'); try { const response = await fetch(catalogCitiesUrl + '?' + params.toString(), {headers, signal: cityAbort.signal}); const cities = await response.json(); cities.forEach(item => city.add(new Option(item.name, item.id, false, String(item.id) === selected))); city.disabled = false; if (selected && [...city.options].some(option => option.value === selected)) city.value = selected; } catch (error) { if (error.name !== 'AbortError') city.disabled = false; } }
    function renderCatalog(fields, count = fields.length) { const results = document.getElementById('catalog-results'); results.replaceChildren(); if (!fields.length) { setCatalogState('موردی با این فیلترها پیدا نشد.'); return; } const summary = document.createElement('div'); summary.className = 'catalog-state'; summary.textContent = formatter.format(count) + ' رشته‌محل پیدا شد.'; results.append(summary); fields.forEach(field => { const row = document.createElement('div'); row.className = 'catalog-result'; row.dataset.catalogResult = '1'; row.dataset.code = field.field_code; const code = document.createElement('span'); code.className = 'catalog-code ltr'; code.textContent = field.field_code; const main = document.createElement('div'); const title = document.createElement('strong'); title.className = 'catalog-title'; title.textContent = field.field_name; const meta = document.createElement('div'); meta.className = 'catalog-meta'; [field.booklet_source, field.university_type || field.course_type, field.capacity ? formatter.format(field.capacity) + ' نفر ظرفیت' : null].filter(Boolean).forEach(text => { const chip = document.createElement('span'); chip.className = 'catalog-chip'; chip.textContent = text; meta.append(chip); }); main.append(title, meta); const place = document.createElement('div'); place.className = 'catalog-result-place'; const inst = document.createElement('div'); inst.textContent = field.university_name || field.institution || '-'; const city = document.createElement('div'); city.className = 'catalog-place'; city.textContent = [field.province, field.city].filter(Boolean).join(' / '); place.append(inst, city); const desc = document.createElement('div'); desc.className = 'catalog-meta'; desc.textContent = field.field_description || field.university_description || 'بدون توضیحات ثبت‌شده'; const action = document.createElement('div'); action.className = 'catalog-result-action'; const add = document.createElement('button'); add.type = 'button'; add.className = 'btn btn-sm btn-primary'; add.dataset.catalogAdd = field.id; add.textContent = 'افزودن به لیست'; add.disabled = itemRows().length >= 150; action.append(add); row.append(code, main, place, desc, action); results.append(row); }); }
    async function runCatalogSearch() { clearTimeout(catalogTimer); if (!canSearchCatalog()) { setCatalogState('برای شروع، حداقل دو حرف از نام رشته را وارد کنید یا یکی از فیلترها را انتخاب کنید.'); return; } catalogAbort?.abort(); catalogAbort = new AbortController(); setCatalogState('در حال جستجو...', 'loading'); try { const payload = await jsonRequest(catalogUrl + '?' + catalogParams().toString(), {signal: catalogAbort.signal}); renderCatalog(payload.items || [], payload.count ?? 0); } catch (error) { if (error.name !== 'AbortError') setCatalogState(error.message); } }
    function scheduleCatalogSearch() { clearTimeout(catalogTimer); catalogTimer = setTimeout(runCatalogSearch, 300); }
    document.addEventListener('input', event => { const input = event.target; if (input.matches('[data-catalog-input]')) { markChanged(input.closest('tr')); clearTimeout(suggestionTimer); suggestionTimer = setTimeout(() => searchCatalog(input), 250); return; } if (input.matches('[data-item-input]')) markChanged(input.closest('[data-item-row]')); });
    document.addEventListener('click', event => { if (!event.target.closest('.catalog-menu') && !event.target.matches('[data-catalog-input]')) closeMenu(); });
    document.getElementById('selection-filter')?.addEventListener('input', function () { const needle = this.value.trim().toLowerCase(); itemRows().forEach(row => { const values = [...row.querySelectorAll('input, textarea')].map(input => input.value).join(' '); row.hidden = needle && !(row.textContent + ' ' + values).toLowerCase().includes(needle); }); });
    document.getElementById('add-first-row')?.addEventListener('click', () => document.querySelector('[data-add-row] [data-field="field_code"]')?.focus());
    document.getElementById('catalog-field-query')?.addEventListener('input', () => { loadCatalogCities(false); scheduleCatalogSearch(); });
    document.getElementById('catalog-province')?.addEventListener('change', () => { loadCatalogCities(false); runCatalogSearch(); });
    document.getElementById('catalog-city')?.addEventListener('change', runCatalogSearch);
    document.getElementById('catalog-course-type')?.addEventListener('change', () => { loadCatalogCities(true); runCatalogSearch(); });
    ['catalog-booklet', 'catalog-provinces', 'catalog-semester', 'catalog-academic-record', 'catalog-gender'].forEach(id => document.getElementById(id)?.addEventListener('change', runCatalogSearch));
    document.getElementById('catalog-search-button')?.addEventListener('click', runCatalogSearch);
    document.getElementById('catalog-reset-button')?.addEventListener('click', () => { document.getElementById('catalog-field-query').value = ''; document.getElementById('catalog-province').value = ''; document.getElementById('catalog-course-type').value = ''; ['catalog-booklet', 'catalog-semester', 'catalog-academic-record', 'catalog-gender'].forEach(id => document.getElementById(id).value = ''); [...document.getElementById('catalog-provinces').options].forEach(option => option.selected = false); loadCatalogCities(false); setCatalogState('برای شروع، حداقل نام رشته را وارد کن یا یکی از فیلترها را انتخاب کن.'); });
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

@push('scripts')
<script>
/* -------------------------------------------------------------
   Province multi-select widget.

   It never owns the data: the native <select multiple id="catalog-provinces">
   remains the single source of truth, so catalogParams() and the existing
   reset button keep working untouched. The widget only flips `selected`
   flags and dispatches `change`, which the existing search listener
   already reacts to.
------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', function () {
    const field = document.querySelector('[data-multiselect]');
    const native = document.getElementById('catalog-provinces');
    if (!field || !native) return;

    const toggle = field.querySelector('[data-ms-toggle]');
    const panel = field.querySelector('[data-ms-panel]');
    const optionsBox = field.querySelector('[data-ms-options]');
    const emptyBox = field.querySelector('[data-ms-empty]');
    const searchInput = field.querySelector('[data-ms-search]');
    const labelEl = field.querySelector('[data-ms-label]');
    const countEl = field.querySelector('[data-ms-count]');
    const chipsBox = document.querySelector('[data-ms-chips]');
    const formatter = new Intl.NumberFormat('fa-IR');

    const options = [...native.options];

    // ---- build the option rows once -------------------------
    const rows = options.map(option => {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'ms-option';
        row.dataset.value = option.value;
        row.setAttribute('role', 'option');

        const box = document.createElement('span');
        box.className = 'ms-option__box';
        box.innerHTML = '<i class="ri-check-line"></i>';

        const text = document.createElement('span');
        text.textContent = option.textContent;

        row.append(box, text);
        row.addEventListener('click', () => {
            option.selected = !option.selected;
            commit();
        });

        optionsBox.append(row);
        return row;
    });

    function selectedOptions() {
        return options.filter(option => option.selected);
    }

    function render() {
        const chosen = selectedOptions();

        rows.forEach((row, index) => row.classList.toggle('is-selected', options[index].selected));

        if (chosen.length === 0) {
            labelEl.textContent = 'همه استان‌ها';
            labelEl.classList.add('is-placeholder');
            countEl.hidden = true;
        } else if (chosen.length === 1) {
            labelEl.textContent = chosen[0].textContent;
            labelEl.classList.remove('is-placeholder');
            countEl.hidden = true;
        } else {
            labelEl.textContent = formatter.format(chosen.length) + ' استان انتخاب شده';
            labelEl.classList.remove('is-placeholder');
            countEl.hidden = false;
            countEl.textContent = formatter.format(chosen.length);
        }

        chipsBox.replaceChildren();
        chipsBox.hidden = chosen.length === 0;

        chosen.forEach(option => {
            const chip = document.createElement('span');
            chip.className = 'ms-chip';
            chip.append(document.createTextNode(option.textContent));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ms-chip__remove';
            remove.setAttribute('aria-label', 'حذف ' + option.textContent);
            remove.innerHTML = '<i class="ri-close-line"></i>';
            remove.addEventListener('click', () => {
                option.selected = false;
                commit();
            });

            chip.append(remove);
            chipsBox.append(chip);
        });

        if (chosen.length > 1) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'ms-chips__clear';
            clear.textContent = 'حذف همه';
            clear.addEventListener('click', () => {
                options.forEach(option => (option.selected = false));
                commit();
            });
            chipsBox.append(clear);
        }
    }

    // re-render, then let the page's own search listener run
    function commit() {
        render();
        native.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function filterOptions(term) {
        const needle = term.trim();
        let visible = 0;

        rows.forEach((row, index) => {
            const match = needle === '' || options[index].textContent.includes(needle);
            row.hidden = !match;
            if (match) visible++;
        });

        emptyBox.hidden = visible > 0;
    }

    function open() {
        if (toggle.disabled) return;
        field.classList.add('is-open');
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        searchInput.value = '';
        filterOptions('');
        searchInput.focus();
    }

    function close() {
        field.classList.remove('is-open');
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', () => (panel.hidden ? open() : close()));
    searchInput.addEventListener('input', () => filterOptions(searchInput.value));

    field.querySelector('[data-ms-all]').addEventListener('click', () => {
        rows.forEach((row, index) => {
            if (!row.hidden) options[index].selected = true;
        });
        commit();
    });

    field.querySelector('[data-ms-none]').addEventListener('click', () => {
        options.forEach(option => (option.selected = false));
        commit();
    });

    document.addEventListener('click', event => {
        if (!field.contains(event.target)) close();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) {
            close();
            toggle.focus();
        }
    });

    // the reset button clears the native select without firing `change`,
    // so mirror it back into the widget afterwards
    document.getElementById('catalog-reset-button')?.addEventListener('click', () => {
        window.setTimeout(render, 0);
    });

    render();
});

/* -------------------------------------------------------------
   Toolbar "more actions" menu.
   Registered separately from the multi-select so it also works
   on archived plans, where the editing scripts are not printed.
------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('[data-toolbar-menu]');
    if (!menu) return;

    const toggle = menu.querySelector('[data-toolbar-menu-toggle]');
    const panel = menu.querySelector('[data-toolbar-menu-panel]');

    function close() {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function () {
        panel.hidden = !panel.hidden;
        toggle.setAttribute('aria-expanded', String(!panel.hidden));
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target)) close();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            close();
            toggle.focus();
        }
    });
});
</script>
@endpush

@push('scripts')
@if($editable)
<script>
/* -------------------------------------------------------------
   Row reordering for touch devices + precise jumps.

   The page already reorders rows with the HTML5 drag-and-drop API,
   which never fires from a finger on iOS/Android. This adds:
     1. a pointer-based drag that handles touch and pen
     2. per-row up/down buttons
     3. clicking the priority number to jump to any position

   It deliberately does NOT touch the existing mouse drag. After any
   move it dispatches `dragend` on the row, which is what the original
   script already listens for to re-balance the two columns and
   renumber - so there stays exactly one source of truth for that.
------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', function () {
    const lists = [...document.querySelectorAll('.selection-list')];
    if (!lists.length) return;

    const ROW = '[data-item-row]';

    function itemRows() {
        return lists.flatMap(list => [...list.querySelectorAll(ROW)]);
    }

    function markDirty() {
        document.getElementById('selection-dirty')?.classList.add('is-visible');
    }

    // hand back to the original script: it re-balances columns and renumbers
    function settle(row) {
        row.dispatchEvent(new Event('dragend'));
    }

    /* ---- shared move primitive ---------------------------------
       targetIndex is the wanted position in the flattened list. */
    function moveRowTo(row, targetIndex) {
        const others = itemRows().filter(candidate => candidate !== row);
        const index = Math.max(0, Math.min(targetIndex, others.length));
        const reference = others[index];

        if (reference) {
            reference.parentNode.insertBefore(row, reference);
        } else {
            // past the last row - park it at the end of the second column
            const lastList = lists[lists.length - 1];
            lastList.insertBefore(row, lastList.querySelector('[data-add-row]'));
        }

        markDirty();
        settle(row);
    }

    function moveRowBy(row, delta) {
        const rows = itemRows();
        const from = rows.indexOf(row);
        if (from < 0) return;

        const to = from + delta;
        if (to < 0 || to >= rows.length) return;

        moveRowTo(row, to);
    }

    /* ---- 1. up / down buttons ---------------------------------- */
    document.addEventListener('click', function (event) {
        const up = event.target.closest('[data-move-up]');
        const down = event.target.closest('[data-move-down]');
        if (!up && !down) return;

        const trigger = up || down;
        const row = trigger.closest(ROW);
        if (!row) return;

        event.preventDefault();
        moveRowBy(row, up ? -1 : 1);
        row.querySelector(up ? '[data-move-up]' : '[data-move-down]')?.focus();
    });

    // keep the first/last row from offering a move that cannot happen
    function syncStepButtons() {
        const rows = itemRows();
        rows.forEach((row, index) => {
            const up = row.querySelector('[data-move-up]');
            const down = row.querySelector('[data-move-down]');
            if (up) up.disabled = index === 0;
            if (down) down.disabled = index === rows.length - 1;
        });
    }

    /* ---- 2. click the number to jump to a priority -------------- */
    let openJump = null;

    function closeJump(apply) {
        if (!openJump) return;

        const badge = openJump.badge;
        const input = openJump.input;
        const row = openJump.row;
        const value = parseInt(input.value, 10);

        openJump = null;
        input.remove();
        badge.hidden = false;

        if (apply && !Number.isNaN(value)) {
            moveRowTo(row, Math.max(1, value) - 1);
        }
    }

    document.addEventListener('click', function (event) {
        const badge = event.target.closest('[data-move-to]');

        if (!badge) {
            if (openJump && event.target !== openJump.input) closeJump(false);
            return;
        }

        event.preventDefault();
        closeJump(false);

        const row = badge.closest(ROW);
        const current = itemRows().indexOf(row) + 1;

        const input = document.createElement('input');
        input.type = 'number';
        input.className = 'selection-jump';
        input.min = '1';
        input.max = String(itemRows().length);
        input.value = String(current);
        input.setAttribute('aria-label', 'انتقال به اولویت');
        input.title = 'شماره اولویت جدید را وارد کنید و Enter بزنید';

        badge.hidden = true;
        badge.after(input);
        openJump = { badge: badge, input: input, row: row };

        input.focus();
        input.select();

        input.addEventListener('keydown', function (keyEvent) {
            if (keyEvent.key === 'Enter') {
                keyEvent.preventDefault();
                closeJump(true);
            } else if (keyEvent.key === 'Escape') {
                keyEvent.preventDefault();
                closeJump(false);
            }
        });

        input.addEventListener('blur', function () {
            window.setTimeout(function () { closeJump(false); }, 0);
        });
    });

    /* ---- 3. pointer drag (touch + pen) -------------------------- */
    let drag = null;

    function rowUnderPoint(x, y) {
        const stack = document.elementsFromPoint(x, y);
        for (const element of stack) {
            const candidate = element.closest ? element.closest(ROW) : null;
            if (candidate && candidate !== drag.row) return candidate;
        }
        return null;
    }

    function autoScroll(y) {
        const margin = 90;
        if (y < margin) window.scrollBy(0, -14);
        else if (y > window.innerHeight - margin) window.scrollBy(0, 14);
    }

    document.addEventListener('pointerdown', function (event) {
        // mouse keeps using the original HTML5 drag-and-drop untouched
        if (event.pointerType === 'mouse') return;

        const handle = event.target.closest('.drag-handle');
        if (!handle) return;

        const row = handle.closest(ROW);
        if (!row) return;

        event.preventDefault();
        drag = { row: row, handle: handle, moved: false };
        row.classList.add('is-touch-dragging');

        try {
            handle.setPointerCapture(event.pointerId);
        } catch (error) {
            /* capture is a nicety, the document listeners still work */
        }
    });

    document.addEventListener('pointermove', function (event) {
        if (!drag) return;

        event.preventDefault();
        drag.moved = true;
        autoScroll(event.clientY);

        const target = rowUnderPoint(event.clientX, event.clientY);
        if (!target) return;

        const rect = target.getBoundingClientRect();
        const insertBefore = event.clientY < rect.top + rect.height / 2;

        target.parentNode.insertBefore(drag.row, insertBefore ? target : target.nextSibling);
    }, { passive: false });

    function endDrag() {
        if (!drag) return;

        const row = drag.row;
        const moved = drag.moved;
        drag = null;

        row.classList.remove('is-touch-dragging');
        if (moved) markDirty();
        settle(row);
    }

    document.addEventListener('pointerup', endDrag);
    document.addEventListener('pointercancel', endDrag);

    /* keep the step buttons in sync after every reorder */
    lists.forEach(function (list) {
        new MutationObserver(syncStepButtons).observe(list, { childList: true });
    });

    syncStepButtons();
});
</script>
@endif
@endpush

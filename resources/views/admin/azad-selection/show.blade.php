@extends('layouts.admin')

@section('title', 'مدیریت انتخاب رشته آزاد')

@push('styles')
<style>
    .azad-toolbar .card-body { padding: .9rem 1.1rem; }
    .azad-toolbar__top { display: flex; align-items: center; flex-wrap: wrap; gap: .75rem; }
    .azad-toolbar__title { flex: 1 1 240px; min-width: 0; }
    .azad-toolbar__title strong { display: block; font-size: .98rem; line-height: 1.7; }
    .azad-badges { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: .25rem; font-size: .82rem; color: var(--ink-500); }
    .azad-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .85rem; padding-top: .85rem; border-top: 1px solid var(--border); }
    .azad-actions form { display: flex; align-items: center; gap: .5rem; margin: 0; }
    .azad-actions .form-check-label { font-size: .78rem; }
    .azad-menu { position: relative; margin-inline-start: auto; }
    .azad-menu__panel { position: absolute; z-index: 40; top: calc(100% + 6px); inset-inline-end: 0; min-width: 232px; padding: .3rem; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow-md); }
    .azad-menu__panel form { margin: 0; }
    .azad-menu__item { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .5rem .6rem; border: 0; border-radius: 8px; background: transparent; color: var(--ink-700); font-family: inherit; font-size: .83rem; text-align: right; text-decoration: none; cursor: pointer; }
    .azad-menu__item:hover { background: var(--brand-50); color: var(--brand-700); }
    .azad-menu__item.is-danger:hover { background: #fcecea; color: var(--danger); }
    .azad-filter-grid .form-label { font-size: .78rem; margin-bottom: .25rem; color: var(--ink-700); }
    .azad-results { max-height: 460px; overflow: auto; border: 1px solid var(--border); border-radius: var(--radius-sm); }
    .azad-results table, .azad-list table { width: 100%; border-collapse: collapse; }
    .azad-results th, .azad-results td, .azad-list th, .azad-list td { padding: .45rem .5rem; border-bottom: 1px solid var(--border); font-size: .82rem; vertical-align: middle; }
    .azad-results th, .azad-list th { position: sticky; top: 0; background: var(--bg); color: var(--ink-500); font-weight: 600; white-space: nowrap; z-index: 1; }
    .azad-results tr:hover td, .azad-row:hover td { background: var(--brand-50); }
    .azad-state { padding: .9rem; color: var(--ink-500); font-size: .84rem; }
    .azad-code { font-weight: 700; color: var(--brand-700); white-space: nowrap; }
    .azad-number { width: 2rem; height: 2rem; border-radius: 50%; background: var(--brand-50); color: var(--brand-700); display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; }
    .azad-move { border: 0; background: transparent; color: var(--ink-500); padding: .15rem; }
    .azad-move[data-drag] { cursor: grab; }
    .azad-row.dragging { opacity: .4; }
    .azad-note { min-width: 160px; }
    .azad-chip { display: inline-flex; border: 1px solid var(--border); border-radius: 999px; padding: .02rem .4rem; font-size: .7rem; background: var(--bg); margin-inline-start: .2rem; white-space: nowrap; }
    .azad-toast { position: fixed; left: 1rem; bottom: 1rem; z-index: 1100; display: none; max-width: 340px; padding: .8rem 1rem; border-radius: var(--radius-sm); color: #fff; box-shadow: var(--shadow-md); font-size: .85rem; }
    .azad-toast.show { display: block; }
    .azad-toast.success { background: var(--success); }
    .azad-toast.danger { background: var(--danger); }
    @media (max-width: 767.98px) { .azad-menu { flex: 1 1 100%; margin-inline-start: 0; } .azad-menu__panel { inset-inline: 0; } }
</style>
@endpush

@section('content')
    @php($items = $plan->items)
    @php($student = $reservation->student)
    @php($n = fn ($v) => \App\Support\PersianDate::number($v))

    <div class="card azad-toolbar mb-3">
        <div class="card-body">
            <div class="azad-toolbar__top">
                <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.show', $reservation) }}" title="بازگشت به رزرو"><i class="ri-arrow-right-line"></i> بازگشت</a>
                <div class="azad-toolbar__title">
                    <strong>انتخاب رشته دانشگاه آزاد@if($student) | {{ $student->full_name }}@endif</strong>
                    <div class="azad-badges">
                        <span><span id="item-counter">{{ $n($items->count()) }}</span> از {{ $n(\App\Models\AzadSelectionPlan::MAX_ITEMS) }} مورد</span>
                        <span class="badge text-bg-{{ $plan->isPublished() ? 'success' : ($plan->isArchived() ? 'secondary' : 'warning') }}">{{ $plan->statusLabel() }}</span>
                        @if($plan->isPublished())
                            <span class="badge text-bg-{{ $plan->is_public_visible ? 'info' : 'light' }}">{{ $plan->is_public_visible ? 'قابل مشاهده برای دانش‌آموز' : 'مخفی از دانش‌آموز' }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="azad-actions">
                @if($editable && $plan->isDraft())
                    <form method="post" action="{{ route('admin.azad-selection-plans.publish', $plan) }}" onsubmit="return confirm('لیست انتخاب رشته آزاد منتشر شود؟')">
                        @csrf
                        <div class="form-check form-check-inline m-0"><input class="form-check-input" type="checkbox" name="visible_to_student" value="1" id="visible-to-student"><label class="form-check-label" for="visible-to-student">بعد از انتشار برای دانش‌آموز قابل مشاهده باشد</label></div>
                        <button class="btn btn-success" id="publish-button" @disabled($items->isEmpty())><i class="ri-send-plane-line"></i> انتشار</button>
                    </form>
                @endif
                <div class="azad-menu" data-menu>
                    <button type="button" class="btn btn-outline-secondary" data-menu-toggle aria-haspopup="true" aria-expanded="false"><i class="ri-more-2-fill"></i> عملیات بیشتر</button>
                    <div class="azad-menu__panel" data-menu-panel hidden>
                        <a class="azad-menu__item" target="_blank" href="{{ route('admin.azad-selection-plans.print', $plan) }}"><i class="ri-printer-line"></i> چاپ انتخاب رشته آزاد</a>
                        @can('manage_azad_field_selection')
                            @if($plan->isPublished())
                                <form method="post" action="{{ route($plan->is_public_visible ? 'admin.azad-selection-plans.hide-from-student' : 'admin.azad-selection-plans.show-to-student', $plan) }}">
                                    @csrf
                                    <button class="azad-menu__item" type="submit" onclick="return confirm('{{ $plan->is_public_visible ? 'لیست از دید دانش‌آموز مخفی شود؟' : 'لیست برای دانش‌آموز قابل مشاهده شود؟' }}')">
                                        <i class="{{ $plan->is_public_visible ? 'ri-eye-off-line' : 'ri-eye-line' }}"></i> {{ $plan->is_public_visible ? 'مخفی کردن از دانش‌آموز' : 'نمایش به دانش‌آموز' }}
                                    </button>
                                </form>
                            @endif
                            @unless($plan->isArchived())
                                <form method="post" action="{{ route('admin.azad-selection-plans.archive', $plan) }}">
                                    @csrf
                                    <button class="azad-menu__item" type="submit" onclick="return confirm('این لیست آرشیو شود؟')"><i class="ri-archive-line"></i> آرشیو کردن</button>
                                </form>
                            @endunless
                            <form method="post" action="{{ route('admin.azad-selection-plans.destroy', $plan) }}" onsubmit="return confirm('انتخاب رشته آزاد حذف شود؟')">
                                @csrf @method('DELETE')
                                <button class="azad-menu__item is-danger" type="submit"><i class="ri-delete-bin-6-line"></i> حذف انتخاب رشته آزاد</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($plan->isPublished() && ! $plan->is_public_visible)
        <div class="alert alert-warning py-2">این لیست منتشر شده است اما هنوز برای دانش‌آموز قابل مشاهده نیست.</div>
    @endif

    @if($editable)
        <div class="card mb-3">
            <div class="card-header"><i class="ri-hashtag"></i> ثبت با کد محل دانشگاهی و کد رشته تحصیلی</div>
            <div class="card-body">
                <form class="row g-2 align-items-end" id="lookup-form" autocomplete="off">
                    <div class="col-6 col-md-2"><label class="form-label">کد محل دانشگاهی</label><input class="form-control ltr" name="unit_code" inputmode="numeric" required></div>
                    <div class="col-6 col-md-2"><label class="form-label">کد رشته تحصیلی</label><input class="form-control ltr" name="field_code" inputmode="numeric" required></div>
                    <div class="col-md-3">
                        <label class="form-label">دفترچه (اختیاری)</label>
                        <select class="form-select" name="booklet">
                            <option value="">همه دفترچه‌ها</option>
                            @foreach(\App\Models\AzadProgram::BOOKLETS as $key => [$label])<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">توضیح (اختیاری)</label><input class="form-control" name="note" maxlength="1000"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ri-search-line"></i> پیدا کردن</button></div>
                </form>
                <div class="small text-muted mt-2">نام محل دانشگاهی، رشته، شهر و بقیه مشخصات از دفترچه پر می‌شود. اگر یک کد در چند دفترچه یا به‌صورت پاره وقت هم آمده باشد، همه موارد نمایش داده می‌شود تا یکی را انتخاب کنید.</div>
                <div class="azad-results mt-2" id="lookup-results" hidden></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="ri-filter-3-line"></i> جستجوی رشته‌محل آزاد</div>
            <div class="card-body">
                <form class="row g-2 azad-filter-grid" id="search-form" autocomplete="off">
                    <div class="col-md-3">
                        <label class="form-label">دفترچه / مقطع</label>
                        <select class="form-select" name="booklet">
                            <option value="">همه دفترچه‌ها</option>
                            @foreach(\App\Models\AzadProgram::BOOKLETS as $key => [$label])<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">نوع پذیرش</label>
                        <select class="form-select" name="admission">
                            <option value="">همه</option>
                            @foreach(\App\Models\AzadProgram::ADMISSIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">استان</label>
                        <select class="form-select" name="province" id="search-province">
                            <option value="">همه استان‌ها</option>
                            @foreach($provinces as $province)<option value="{{ $province }}">{{ $province }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">شهر</label>
                        <select class="form-select" name="city" id="search-city" disabled><option value="">ابتدا استان را انتخاب کنید</option></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">محل دانشگاهی (واحد / مرکز)</label>
                        <select class="form-select" name="unit_code" id="search-unit"><option value="">همه واحدها</option></select>
                    </div>
                    <div class="col-md-2"><label class="form-label">کد رشته</label><input class="form-control ltr" name="field_code" inputmode="numeric"></div>
                    <div class="col-md-3"><label class="form-label">نام رشته، واحد یا شهر</label><input class="form-control" name="q" placeholder="مثلاً حسابداری"></div>
                    <div class="col-md-2">
                        <label class="form-label">گروه آزمایشی / آموزشی</label>
                        <select class="form-select" name="group">
                            <option value="">همه</option>
                            @foreach(\App\Models\AzadProgram::GROUPS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">جنس پذیرش</label>
                        <select class="form-select" name="gender">
                            <option value="">همه</option>
                            <option value="زن">پذیرش زن (زن، زن و مرد)</option>
                            <option value="مرد">پذیرش مرد (مرد، زن و مرد)</option>
                            <option value="only_mixed">فقط زن و مرد</option>
                            <option value="only_female">فقط زن</option>
                            <option value="only_male">فقط مرد</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">پاره وقت</label>
                        <select class="form-select" name="part_time"><option value="">همه</option><option value="1">بله</option><option value="0">خیر</option></select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">خودگردان</label>
                        <select class="form-select" name="self_funded"><option value="">همه</option><option value="1">بله</option><option value="0">خیر</option></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">ظرفیت نیمسال</label>
                        <select class="form-select" name="semester"><option value="">همه</option><option value="first">نیمسال اول</option><option value="second">نیمسال دوم</option></select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button class="btn btn-primary flex-grow-1"><i class="ri-search-line"></i> جستجو</button>
                        <button class="btn btn-outline-secondary" type="reset" title="حذف فیلترها"><i class="ri-refresh-line"></i></button>
                    </div>
                </form>
                <div class="azad-results mt-3" id="search-results"><div class="azad-state">استان، دفترچه یا هر فیلتر دیگری را انتخاب کنید و جستجو را بزنید.</div></div>
            </div>
        </div>
    @endif

    <div class="card azad-list">
        <div class="card-header"><i class="ri-list-ordered"></i> لیست انتخاب رشته آزاد به ترتیب اولویت</div>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    @if($editable)<th></th>@endif
                    <th>ردیف</th><th>کد محل</th><th>محل دانشگاهی</th><th>کد رشته</th><th>رشته تحصیلی</th><th>دفترچه</th><th>استان / شهر</th><th>جنس پذیرش</th><th>ظرفیت (نیمسال اول / دوم)</th><th>توضیح</th>
                    @if($editable)<th></th>@endif
                </tr>
                </thead>
                <tbody id="item-list">
                @foreach($items as $item)
                    @include('admin.azad-selection._item', ['item' => $item, 'editable' => $editable])
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="azad-state text-center" id="empty-list" @if($items->isNotEmpty()) hidden @endif>هنوز رشته‌محلی ثبت نشده است.</div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const menu = document.querySelector('[data-menu]');
    menu?.querySelector('[data-menu-toggle]').addEventListener('click', event => {
        const panel = menu.querySelector('[data-menu-panel]');
        panel.hidden = !panel.hidden;
        event.currentTarget.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.addEventListener('click', event => { if (menu && !menu.contains(event.target)) menu.querySelector('[data-menu-panel]').hidden = true; });

    @if($editable)
    const urls = {
        lookup: @json(route('admin.azad-selection.lookup')),
        search: @json(route('admin.azad-selection.search')),
        cities: @json(route('admin.azad-programs.cities')),
        units: @json(route('admin.azad-programs.units')),
        add: @json(route('admin.azad-selection-plans.items.store', $plan)),
        reorder: @json(route('admin.azad-selection-plans.reorder', $plan)),
    };
    const maxItems = {{ \App\Models\AzadSelectionPlan::MAX_ITEMS }};
    const csrf = @json(csrf_token());
    const fa = new Intl.NumberFormat('fa-IR', { useGrouping: false });
    const list = document.getElementById('item-list');
    const toast = Object.assign(document.createElement('div'), { className: 'azad-toast' });
    document.body.append(toast);
    let toastTimer, orderTimer, dragged;

    const showToast = (message, tone = 'success') => {
        clearTimeout(toastTimer);
        toast.textContent = message;
        toast.className = 'azad-toast show ' + tone;
        toastTimer = setTimeout(() => { toast.className = 'azad-toast'; }, 3600);
    };
    async function request(url, options = {}) {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(payload.errors || {})[0]?.[0] || payload.message || 'عملیات انجام نشد.');
        return payload;
    }
    const rows = () => [...list.querySelectorAll('[data-item-row]')];
    const inList = () => new Set(rows().map(row => row.dataset.programId));
    function refreshList() {
        const all = rows();
        all.forEach((row, index) => { row.querySelector('[data-order]').textContent = fa.format(index + 1); });
        document.getElementById('item-counter').textContent = fa.format(all.length);
        document.getElementById('empty-list').hidden = all.length > 0;
        const publish = document.getElementById('publish-button');
        if (publish) publish.disabled = all.length === 0;
        const added = inList();
        document.querySelectorAll('[data-add]').forEach(button => {
            const already = added.has(button.dataset.add);
            button.disabled = already || all.length >= maxItems;
            button.textContent = already ? 'در لیست است' : 'افزودن';
        });
    }

    function cell(row, text, className) {
        const td = row.insertCell();
        if (className) td.className = className;
        if (text instanceof Node) td.append(text); else td.textContent = text ?? '-';
        return td;
    }
    function renderPrograms(container, programs, summary) {
        container.replaceChildren();
        container.hidden = false;
        if (summary) container.append(Object.assign(document.createElement('div'), { className: 'azad-state', textContent: summary }));
        if (!programs.length) return;
        const table = document.createElement('table');
        table.createTHead().innerHTML = '<tr><th>کد محل</th><th>محل دانشگاهی</th><th>کد رشته</th><th>رشته تحصیلی</th><th>دفترچه</th><th>استان / شهر</th><th>جنس پذیرش</th><th>ظرفیت (نیمسال اول / دوم)</th><th></th></tr>';
        const body = table.createTBody();
        programs.forEach(p => {
            const row = body.insertRow();
            cell(row, p.unit_code, 'azad-code ltr');
            cell(row, p.unit_name);
            cell(row, p.field_code, 'azad-code ltr');
            const name = document.createElement('span');
            name.textContent = p.field_name + (p.part_time ? ' (پاره وقت)' : '');
            [p.exam_group, p.education_group ? 'گروه آموزشی: ' + p.education_group : null, p.self_funded ? 'ظرفیت خودگردان' : null]
                .filter(Boolean).forEach(text => name.append(Object.assign(document.createElement('span'), { className: 'azad-chip', textContent: text })));
            cell(row, name);
            cell(row, p.booklet_label);
            const place = document.createElement('span');
            place.append(p.province, Object.assign(document.createElement('small'), { className: 'd-block text-muted', textContent: p.city }));
            cell(row, place);
            cell(row, p.gender);
            cell(row, p.admission === 'exam' ? (p.capacity_first ?? '-') + ' / ' + (p.capacity_second ?? '-') : 'سوابق', 'text-nowrap');
            const add = Object.assign(document.createElement('button'), { type: 'button', className: 'btn btn-sm btn-primary text-nowrap', textContent: 'افزودن' });
            add.dataset.add = String(p.id);
            cell(row, add);
        });
        container.append(table);
        refreshList();
    }

    async function addProgram(button) {
        button.disabled = true;
        const note = button.closest('#lookup-results') ? document.querySelector('#lookup-form [name="note"]').value : '';
        try {
            const payload = await request(urls.add, { method: 'POST', body: JSON.stringify({ azad_program_id: Number(button.dataset.add), note }) });
            list.insertAdjacentHTML('beforeend', payload.html);
            refreshList();
            showToast(payload.message);
            if (button.closest('#lookup-results')) {
                const form = document.getElementById('lookup-form');
                form.reset();
                document.getElementById('lookup-results').hidden = true;
                form.unit_code.focus();
            }
        } catch (error) {
            showToast(error.message, 'danger');
            refreshList();
        }
    }
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-add]');
        if (button) addProgram(button);
    });

    document.getElementById('lookup-form').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const results = document.getElementById('lookup-results');
        const params = new URLSearchParams({ unit_code: form.unit_code.value.trim(), field_code: form.field_code.value.trim(), booklet: form.booklet.value });
        try {
            const payload = await request(urls.lookup + '?' + params);
            renderPrograms(results, payload.items, payload.count ? null : 'رشته‌محلی با این کد محل و کد رشته پیدا نشد.');
            if (payload.count === 1) results.querySelector('[data-add]:not(:disabled)')?.focus();
        } catch (error) {
            showToast(error.message, 'danger');
        }
    });

    const searchForm = document.getElementById('search-form');
    const province = document.getElementById('search-province');
    const city = document.getElementById('search-city');
    const unit = document.getElementById('search-unit');
    const scope = () => new URLSearchParams({ booklet: searchForm.booklet.value, admission: searchForm.admission.value, province: province.value, city: city.disabled ? '' : city.value });
    let citiesRequest = 0, unitsRequest = 0;
    async function loadCities() {
        const ticket = ++citiesRequest;
        city.innerHTML = '';
        city.add(new Option(province.value ? 'همه شهرهای استان' : 'ابتدا استان را انتخاب کنید', ''));
        city.disabled = !province.value;
        if (!province.value) return;
        const names = await request(urls.cities + '?' + scope());
        if (ticket !== citiesRequest) return;
        names.forEach(name => city.add(new Option(name, name)));
    }
    async function loadUnits() {
        const ticket = ++unitsRequest;
        const selected = unit.value;
        const units = await request(urls.units + '?' + scope());
        if (ticket !== unitsRequest) return;
        unit.innerHTML = '';
        unit.add(new Option('همه واحدها', ''));
        units.forEach(u => unit.add(new Option(u.name + ' (' + u.code + ')', u.code, false, u.code === selected)));
    }
    province.addEventListener('change', async () => { await loadCities(); loadUnits(); });
    city.addEventListener('change', loadUnits);
    searchForm.booklet.addEventListener('change', loadUnits);
    searchForm.admission.addEventListener('change', loadUnits);
    searchForm.addEventListener('reset', () => setTimeout(async () => { await loadCities(); loadUnits(); }));
    searchForm.addEventListener('submit', async event => {
        event.preventDefault();
        const results = document.getElementById('search-results');
        const params = new URLSearchParams(new FormData(searchForm));
        results.replaceChildren(Object.assign(document.createElement('div'), { className: 'azad-state', textContent: 'در حال جستجو...' }));
        try {
            const payload = await request(urls.search + '?' + params);
            const summary = !payload.total ? 'موردی با این فیلترها پیدا نشد. حداقل یک فیلتر (یا دو حرف از نام) لازم است.'
                : payload.total > payload.count ? fa.format(payload.total) + ' رشته‌محل پیدا شد؛ ' + fa.format(payload.count) + ' مورد اول نمایش داده شده است. برای دیدن بقیه، فیلترها را دقیق‌تر کنید.'
                : fa.format(payload.total) + ' رشته‌محل پیدا شد.';
            renderPrograms(results, payload.items, summary);
        } catch (error) {
            results.replaceChildren(Object.assign(document.createElement('div'), { className: 'azad-state', textContent: error.message }));
        }
    });
    loadUnits();

    list.addEventListener('change', async event => {
        const input = event.target.closest('[data-note]');
        if (!input) return;
        try {
            showToast((await request(input.dataset.url, { method: 'PUT', body: JSON.stringify({ note: input.value }) })).message);
        } catch (error) { showToast(error.message, 'danger'); }
    });
    list.addEventListener('click', async event => {
        const remove = event.target.closest('[data-delete]');
        if (remove) {
            if (!confirm('این رشته‌محل از لیست حذف شود؟')) return;
            try {
                const payload = await request(remove.dataset.url, { method: 'DELETE' });
                remove.closest('[data-item-row]').remove();
                refreshList();
                showToast(payload.message);
            } catch (error) { showToast(error.message, 'danger'); }
            return;
        }
        const row = event.target.closest('[data-item-row]');
        if (event.target.closest('[data-move-up]') && row.previousElementSibling) { row.previousElementSibling.before(row); orderChanged(); }
        if (event.target.closest('[data-move-down]') && row.nextElementSibling) { row.nextElementSibling.after(row); orderChanged(); }
    });
    function orderChanged() {
        refreshList();
        clearTimeout(orderTimer);
        orderTimer = setTimeout(async () => {
            try {
                showToast((await request(urls.reorder, { method: 'POST', body: JSON.stringify({ ordered_item_ids: rows().map(row => Number(row.dataset.itemId)) }) })).message);
            } catch (error) { showToast(error.message + ' صفحه را دوباره باز کنید.', 'danger'); }
        }, 700);
    }
    list.addEventListener('dragstart', event => {
        dragged = event.target.closest('[data-item-row]');
        dragged?.classList.add('dragging');
    });
    list.addEventListener('dragend', () => { dragged?.classList.remove('dragging'); dragged = null; });
    list.addEventListener('dragover', event => {
        if (!dragged) return;
        event.preventDefault();
        const target = event.target.closest('[data-item-row]');
        if (!target || target === dragged) return;
        const box = target.getBoundingClientRect();
        event.clientY < box.top + box.height / 2 ? target.before(dragged) : target.after(dragged);
    });
    list.addEventListener('drop', event => { if (dragged) { event.preventDefault(); orderChanged(); } });
    refreshList();
    @endif
})();
</script>
@endpush

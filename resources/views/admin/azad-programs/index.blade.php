@extends('layouts.admin')

@section('title', 'رشته‌محل‌های دانشگاه آزاد')

@section('subtitle')
    {{ \App\Support\PersianDate::number($programs->total()) }} رشته‌محل
    @if($lastImport)
        · آخرین بارگذاری از دفترچه‌ها: {{ \App\Support\PersianDate::dateTime($lastImport->finished_at ?? $lastImport->created_at) }}
    @endif
@endsection

@section('actions')
    @can('create_azad_programs')
        <a class="btn btn-primary" href="{{ route('admin.azad-programs.create') }}"><i class="ri-add-line align-middle"></i> افزودن رشته‌محل آزاد</a>
    @endcan
@endsection

@push('styles')
<style>
    .azad-filters .form-label { font-size: .8rem; margin-bottom: .3rem; }
    .azad-table td, .azad-table th { vertical-align: middle; font-size: .84rem; }
    .azad-table .code { font-weight: 700; color: var(--brand-700); white-space: nowrap; }
    .azad-tags { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .2rem; }
    .azad-tag { display: inline-flex; border: 1px solid var(--border); border-radius: 999px; padding: .05rem .45rem; font-size: .72rem; background: var(--bg); white-space: nowrap; }
    .azad-tag.is-warning { border-color: var(--warning); background: #fff8e7; }
</style>
@endpush

@section('content')
    @php($f = fn (string $key, $default = '') => (string) ($filters[$key] ?? $default))
    <div class="card mb-3 azad-filters">
        <div class="card-body">
            <form class="row g-3" method="get" id="azadFilters">
                <div class="col-md-2">
                    <label class="form-label">سال</label>
                    <select name="year" id="yearSelect" class="form-select">
                        <option value="">همه</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($f('year') === (string) $year)>{{ \App\Support\PersianDate::number($year) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">دفترچه / مقطع</label>
                    <select name="booklet" id="bookletSelect" class="form-select">
                        <option value="">همه دفترچه‌ها</option>
                        @foreach(\App\Models\AzadProgram::BOOKLETS as $key => [$label])
                            <option value="{{ $key }}" @selected($f('booklet') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">نوع پذیرش</label>
                    <select name="admission" id="admissionSelect" class="form-select">
                        <option value="">همه</option>
                        @foreach(\App\Models\AzadProgram::ADMISSIONS as $key => $label)
                            <option value="{{ $key }}" @selected($f('admission') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">استان</label>
                    <select name="province" id="provinceSelect" class="form-select">
                        <option value="">همه استان‌ها</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" @selected($f('province') === $province)>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">شهر</label>
                    <select name="city" id="citySelect" class="form-select" @disabled($f('province') === '')>
                        <option value="">{{ $f('province') === '' ? 'ابتدا استان را انتخاب کنید' : 'همه شهرهای استان' }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" @selected($f('city') === $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">محل دانشگاهی (واحد / مرکز)</label>
                    <select name="unit_code" id="unitSelect" class="form-select" data-selected="{{ $f('unit_code') }}">
                        <option value="">همه واحدها</option>
                        @if($f('unit_code') !== '')
                            <option value="{{ $f('unit_code') }}" selected>کد {{ $f('unit_code') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">کد رشته تحصیلی</label>
                    <input name="field_code" value="{{ $f('field_code') }}" class="form-control ltr" inputmode="numeric">
                </div>
                <div class="col-md-3">
                    <label class="form-label">جستجو در نام رشته، واحد و شهر</label>
                    <input name="q" value="{{ $f('q') }}" class="form-control" placeholder="مثلاً پرستاری یا تبریز">
                </div>
                <div class="col-md-3">
                    <label class="form-label">گروه آزمایشی / آموزشی</label>
                    <select name="group" class="form-select">
                        <option value="">همه</option>
                        @foreach(\App\Models\AzadProgram::GROUPS as $key => $label)
                            <option value="{{ $key }}" @selected($f('group') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">جنس پذیرش</label>
                    <select name="gender" class="form-select">
                        <option value="">همه</option>
                        <option value="زن" @selected($f('gender') === 'زن')>پذیرش زن (زن، زن و مرد)</option>
                        <option value="مرد" @selected($f('gender') === 'مرد')>پذیرش مرد (مرد، زن و مرد)</option>
                        <option value="only_mixed" @selected($f('gender') === 'only_mixed')>فقط زن و مرد</option>
                        <option value="only_female" @selected($f('gender') === 'only_female')>فقط زن</option>
                        <option value="only_male" @selected($f('gender') === 'only_male')>فقط مرد</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">پاره وقت</label>
                    <select name="part_time" class="form-select">
                        <option value="">همه</option>
                        <option value="1" @selected($f('part_time') === '1')>فقط پاره وقت</option>
                        <option value="0" @selected($f('part_time') === '0')>بدون پاره وقت</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ظرفیت خودگردان</label>
                    <select name="self_funded" class="form-select">
                        <option value="">همه</option>
                        <option value="1" @selected($f('self_funded') === '1')>فقط خودگردان</option>
                        <option value="0" @selected($f('self_funded') === '0')>بدون خودگردان</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ظرفیت نیمسال</label>
                    <select name="semester" class="form-select">
                        <option value="">همه</option>
                        <option value="first" @selected($f('semester') === 'first')>دارای ظرفیت نیمسال اول</option>
                        <option value="second" @selected($f('semester') === 'second')>دارای ظرفیت نیمسال دوم</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">وضعیت</label>
                    <select name="is_active" class="form-select">
                        <option value="">همه</option>
                        <option value="1" @selected($f('is_active') === '1')>فعال</option>
                        <option value="0" @selected($f('is_active') === '0')>غیرفعال</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="ri-search-line align-middle"></i> جستجو</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.azad-programs.index') }}" title="حذف فیلترها"><i class="ri-refresh-line"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 azad-table">
                <thead>
                <tr>
                    <th>کد محل</th>
                    <th>محل دانشگاهی</th>
                    <th>کد رشته</th>
                    <th>رشته تحصیلی</th>
                    <th>دفترچه</th>
                    <th>استان / شهر</th>
                    <th>جنس پذیرش</th>
                    <th>ظرفیت (نیمسال اول / دوم)</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($programs as $program)
                    <tr>
                        <td class="code ltr">{{ $program->unit_code }}</td>
                        <td>{{ $program->unit_name }}</td>
                        <td class="code ltr">{{ $program->field_code }}</td>
                        <td>
                            {{ $program->field_name }}@if($program->part_time) (پاره وقت)@endif
                            <div class="azad-tags">
                                @if($program->exam_group)<span class="azad-tag">{{ \App\Models\AzadProgram::groupLabel($program->exam_group) }}</span>@endif
                                @if($program->education_group)<span class="azad-tag">گروه آموزشی: {{ \App\Models\AzadProgram::groupLabel($program->education_group) }}</span>@endif
                                @if($program->self_funded)<span class="azad-tag is-warning">ظرفیت خودگردان</span>@endif
                                @if($program->source_type === \App\Models\AzadProgram::SOURCE_MANUAL)<span class="azad-tag is-warning">ثبت یا ویرایش دستی</span>@endif
                            </div>
                        </td>
                        <td>{{ $program->booklet_label }}<small class="d-block text-muted">{{ \App\Support\PersianDate::number($program->year) }}@if($program->booklet_page) · صفحه {{ \App\Support\PersianDate::number($program->booklet_page) }}@endif</small></td>
                        <td>{{ $program->province }}<small class="d-block text-muted">{{ $program->city }}</small></td>
                        <td>{{ $program->gender }}</td>
                        <td class="text-nowrap">@if($program->admission === 'exam'){{ $program->capacityText($program->capacity_first) }} / {{ $program->capacityText($program->capacity_second) }}@else<span class="text-muted">بر اساس سوابق</span>@endif</td>
                        <td><span class="badge bg-{{ $program->is_active ? 'success' : 'secondary' }}">{{ $program->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                        <td class="text-nowrap">@can('update_azad_programs')<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.azad-programs.edit', $program) }}">ویرایش</a>@endcan</td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="empty-state">رشته‌محلی با این فیلترها پیدا نشد.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($programs->hasPages())
            <div class="p-3 d-flex justify-content-center">{{ $programs->links() }}</div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('azadFilters');
            const province = document.getElementById('provinceSelect');
            const city = document.getElementById('citySelect');
            const unit = document.getElementById('unitSelect');
            const citiesUrl = @json(route('admin.azad-programs.cities'));
            const unitsUrl = @json(route('admin.azad-programs.units'));
            const scope = () => new URLSearchParams({
                year: form.year.value, booklet: form.booklet.value, admission: form.admission.value,
                province: province.value, city: city.disabled ? '' : city.value, is_active: form.is_active.value,
            });

            let citiesRequest = 0, unitsRequest = 0;

            async function loadCities() {
                const ticket = ++citiesRequest;
                city.innerHTML = '';
                city.add(new Option(province.value ? 'همه شهرهای استان' : 'ابتدا استان را انتخاب کنید', ''));
                city.disabled = !province.value;
                if (!province.value) return;
                const response = await fetch(`${citiesUrl}?${scope()}`, { headers: { Accept: 'application/json' } });
                const names = await response.json();
                if (ticket !== citiesRequest) return;
                for (const name of names) city.add(new Option(name, name));
            }

            async function loadUnits() {
                const ticket = ++unitsRequest;
                const selected = unit.value || unit.dataset.selected || '';
                const response = await fetch(`${unitsUrl}?${scope()}`, { headers: { Accept: 'application/json' } });
                const units = await response.json();
                if (ticket !== unitsRequest) return;
                unit.innerHTML = '';
                unit.add(new Option('همه واحدها', ''));
                for (const item of units) {
                    unit.add(new Option(`${item.name} (${item.code})`, item.code, false, item.code === selected));
                }
                unit.dataset.selected = '';
            }

            province.addEventListener('change', async () => { await loadCities(); loadUnits(); });
            city.addEventListener('change', loadUnits);
            ['yearSelect', 'bookletSelect', 'admissionSelect'].forEach(id => document.getElementById(id).addEventListener('change', loadUnits));
            loadUnits();
        })();
    </script>
@endpush

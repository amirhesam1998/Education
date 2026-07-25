@extends('layouts.admin')

@section('title', 'رشته‌محل‌ها')

@section('actions')
    <a class="btn btn-outline-primary" href="{{ route('admin.study-programs.reviews') }}">
        <i class="ri-file-list-3-line align-middle"></i> ردیف‌های نیازمند بررسی
    </a>
    <a class="btn btn-outline-secondary" href="{{ route('admin.study-programs.imports') }}">
        <i class="ri-upload-cloud-2-line align-middle"></i> تاریخچه import
    </a>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-2">
                    <label class="form-label">سال</label>
                    <select name="exam_year_id" id="yearSelect" class="form-select">
                        <option value="">همه</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected((int) request('exam_year_id') === $year->id)>{{ \App\Support\PersianDate::number($year->year) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">گروه آزمایشی</label>
                    <select name="exam_group_id" id="groupSelect" class="form-select">
                        <option value="">همه</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) request('exam_group_id') === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">استان</label>
                    <select name="province_id" id="provinceSelect" class="form-select">
                        <option value="">همه</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" @selected((int) request('province_id') === $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">شهر</label>
                    <select name="city_id" id="citySelect" class="form-select" @disabled(! request('province_id'))>
                        <option value="">همه شهرها</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" @selected((int) request('city_id') === $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">دانشگاه / مؤسسه</label>
                    <input id="institutionSearch" class="form-control mb-1" placeholder="جستجو">
                    <select name="institution_id" id="institutionSelect" class="form-select">
                        <option value="">همه</option>
                        @if($selectedInstitution)
                            <option value="{{ $selectedInstitution->id }}" selected>{{ $selectedInstitution->name }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">رشته</label>
                    <input id="fieldSearch" class="form-control mb-1" placeholder="جستجو">
                    <select name="academic_field_id" id="fieldSelect" class="form-select">
                        <option value="">همه</option>
                        @if($selectedAcademicField)
                            <option value="{{ $selectedAcademicField->id }}" selected>{{ $selectedAcademicField->name }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">نوع دوره</label>
                    <select name="course_type_id" class="form-select">
                        <option value="">همه</option>
                        @foreach($courseTypes as $type)
                            <option value="{{ $type->id }}" @selected((int) request('course_type_id') === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">کدرشته</label>
                    <input name="code" value="{{ request('code') }}" class="form-control ltr">
                </div>
                <div class="col-md-2">
                    <label class="form-label">جستجو</label>
                    <input name="search" value="{{ request('search') }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="ri-search-line align-middle"></i> جستجو</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.study-programs.index') }}"><i class="ri-refresh-line"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>کدرشته</th>
                    <th>گروه</th>
                    <th>رشته</th>
                    <th>دانشگاه / مؤسسه</th>
                    <th>شهر</th>
                    <th>استان</th>
                    <th>نوع دوره</th>
                    <th>پذیرش</th>
                    <th>وضعیت توضیحات</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($programs as $program)
                    <tr>
                        <td class="ltr">{{ $program->code }}</td>
                        <td>{{ $program->examGroup?->name }}</td>
                        <td>{{ $program->academicField?->name ?: '-' }}</td>
                        <td>{{ $program->institution?->name ?: '-' }}</td>
                        <td>{{ $program->city?->name ?: '-' }}</td>
                        <td>{{ $program->province?->name ?: '-' }}</td>
                        <td><span class="badge bg-info">{{ $program->courseType?->name ?: '-' }}</span></td>
                        <td><span class="badge bg-secondary">{{ $program->admissionType?->name ?: '-' }}</span></td>
                        <td>{!! $program->description ? '<span class="badge bg-success">دارای توضیحات</span>' : '-' !!}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.study-programs.show', $program) }}">مشاهده</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="empty-state">رشته‌محلی یافت نشد.</div></td></tr>
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
        document.getElementById('provinceSelect')?.addEventListener('change', async function () {
            const city = document.getElementById('citySelect');
            city.innerHTML = '<option value="">همه شهرها</option>';
            city.disabled = !this.value;
            if (!this.value) return;
            const params = new URLSearchParams({
                province_id: this.value,
                exam_year_id: document.getElementById('yearSelect')?.value || '',
                exam_group_id: document.getElementById('groupSelect')?.value || '',
                only_with_programs: '1'
            });
            const response = await fetch(`{{ route('admin.study-programs.filter-options.cities') }}?${params}`);
            for (const item of await response.json()) {
                city.add(new Option(item.name, item.id));
            }
        });

        async function loadRemoteSelect(selectId, searchId, url) {
            const select = document.getElementById(selectId);
            const search = document.getElementById(searchId);
            if (!select || !search) return;
            const load = async () => {
                const params = new URLSearchParams({
                    search: search.value,
                    exam_year_id: document.getElementById('yearSelect')?.value || '',
                    exam_group_id: document.getElementById('groupSelect')?.value || '',
                    province_id: document.getElementById('provinceSelect')?.value || '',
                    city_id: document.getElementById('citySelect')?.value || '',
                    institution_id: document.getElementById('institutionSelect')?.value || '',
                });
                const selected = select.value;
                select.innerHTML = '<option value="">همه</option>';
                const response = await fetch(`${url}?${params}`);
                for (const item of (await response.json()).results) {
                    select.add(new Option(item.text, item.id, false, String(item.id) === selected));
                }
            };
            search.addEventListener('input', load);
            select.addEventListener('focus', load, { once: true });
        }

        loadRemoteSelect('institutionSelect', 'institutionSearch', '{{ route('admin.study-programs.filter-options.institutions') }}');
        loadRemoteSelect('fieldSelect', 'fieldSearch', '{{ route('admin.study-programs.filter-options.academic-fields') }}');
    </script>
@endpush

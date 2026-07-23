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
                    <select name="year" class="form-select">
                        <option value="">همه</option>
                        @foreach($years as $year)
                            <option value="{{ $year->year }}" @selected(request('year') == $year->year)>{{ \App\Support\PersianDate::number($year->year) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">گروه</label>
                    <select name="group" class="form-select">
                        <option value="">همه</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->slug }}" @selected(request('group') === $group->slug)>{{ $group->name }}</option>
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
                    <label class="form-label">نوع دوره</label>
                    <select name="course_type" class="form-select">
                        <option value="">همه</option>
                        @foreach($courseTypes as $type)
                            <option value="{{ $type->slug }}" @selected(request('course_type') === $type->slug)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">کدرشته</label>
                    <input name="code" value="{{ request('code') }}" class="form-control ltr">
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
                    <th>نوع دوره</th>
                    <th>پذیرش</th>
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
                        <td>{{ $program->city?->name ?: $program->province?->name ?: '-' }}</td>
                        <td><span class="badge bg-info">{{ $program->courseType?->name ?: '-' }}</span></td>
                        <td><span class="badge bg-secondary">{{ $program->admissionType?->name ?: '-' }}</span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.study-programs.show', $program) }}">مشاهده</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state">رشته‌محلی یافت نشد.</div></td></tr>
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
            const response = await fetch(`{{ route('admin.study-programs.cities') }}?province_id=${this.value}&only_with_programs=1`);
            for (const item of await response.json()) {
                city.add(new Option(item.name, item.id));
            }
        });
    </script>
@endpush

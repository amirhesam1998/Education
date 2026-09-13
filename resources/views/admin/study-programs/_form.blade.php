@csrf
@isset($program) @method('PUT') @endisset

<div class="card">
    <div class="card-body">
        <div class="alert alert-light border small">برای مواردی که در فهرست نیستند، ابتدا مرجع مرتبط را ایجاد یا انتخاب کنید. این فرم فقط رشته‌محل را ثبت می‌کند.</div>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">سال آزمون</label><select name="exam_year_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected((int) old('exam_year_id', $program->exam_year_id ?? 0) === $year->id)>{{ \App\Support\PersianDate::number($year->year) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">گروه آزمایشی</label><select name="exam_group_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((int) old('exam_group_id', $program->exam_group_id ?? 0) === $group->id)>{{ $group->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">کد رشته</label><input name="code" value="{{ old('code', $program->code ?? '') }}" class="form-control ltr" maxlength="32" required></div>
            <div class="col-md-3"><label class="form-label">نام رشته</label><select name="academic_field_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($academicFields as $field)<option value="{{ $field->id }}" @selected((int) old('academic_field_id', $program->academic_field_id ?? 0) === $field->id)>{{ $field->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">استان</label><select id="program-province" name="province_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($provinces as $province)<option value="{{ $province->id }}" @selected((int) old('province_id', $program->province_id ?? 0) === $province->id)>{{ $province->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">شهر</label><select id="program-city" name="city_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($cities as $city)<option value="{{ $city->id }}" data-province="{{ $city->province_id }}" @selected((int) old('city_id', $program->city_id ?? 0) === $city->id)>{{ $city->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">دانشگاه</label><select id="program-institution" name="institution_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($institutions as $institution)<option value="{{ $institution->id }}" @selected((int) old('institution_id', $program->institution_id ?? 0) === $institution->id)>{{ $institution->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">پردیس</label><select id="program-campus" name="institution_campus_id" class="form-select"><option value="">بدون پردیس</option>@foreach($campuses as $campus)<option value="{{ $campus->id }}" data-institution="{{ $campus->institution_id }}" @selected((int) old('institution_campus_id', $program->institution_campus_id ?? 0) === $campus->id)>{{ $campus->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">نوع دوره</label><select name="course_type_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($courseTypes as $type)<option value="{{ $type->id }}" @selected((int) old('course_type_id', $program->course_type_id ?? 0) === $type->id)>{{ $type->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">نوع پذیرش</label><select name="admission_type_id" class="form-select" required><option value="">انتخاب کنید</option>@foreach($admissionTypes as $type)<option value="{{ $type->id }}" @selected((int) old('admission_type_id', $program->admission_type_id ?? 0) === $type->id)>{{ $type->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">ظرفیت نیمسال اول</label><input name="first_semester_capacity" type="number" min="0" value="{{ old('first_semester_capacity', $program->first_semester_capacity ?? '') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">ظرفیت نیمسال دوم</label><input name="second_semester_capacity" type="number" min="0" value="{{ old('second_semester_capacity', $program->second_semester_capacity ?? '') }}" class="form-control"></div>
            <div class="col-md-8"><label class="form-label">توضیحات</label><textarea name="description" class="form-control" rows="3">{{ old('description', $program->description ?? '') }}</textarea></div>
            <div class="col-md-4"><label class="form-label">وضعیت</label><select name="is_active" class="form-select"><option value="1" @selected((string) old('is_active', $program->is_active ?? true) === '1')>فعال</option><option value="0" @selected((string) old('is_active', $program->is_active ?? true) === '0')>غیرفعال</option></select></div>
        </div>
    </div>
</div>
<div class="mt-3 d-flex gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="{{ route('admin.study-programs.index') }}">بازگشت</a><button class="btn btn-primary">ذخیره</button></div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const province = document.getElementById('program-province'), city = document.getElementById('program-city'), institution = document.getElementById('program-institution'), campus = document.getElementById('program-campus');
        const filter = (select, attribute, value) => [...select.options].forEach(option => { option.hidden = Boolean(option.value && option.dataset[attribute] !== value); });
        province?.addEventListener('change', () => { filter(city, 'province', province.value); if (city.selectedOptions[0]?.hidden) city.value = ''; });
        institution?.addEventListener('change', () => { filter(campus, 'institution', institution.value); if (campus.selectedOptions[0]?.hidden) campus.value = ''; });
        province?.dispatchEvent(new Event('change')); institution?.dispatchEvent(new Event('change'));
    });
</script>
@endpush

@extends('layouts.admin')

@section('title', $program->exists ? 'ویرایش رشته‌محل آزاد' : 'افزودن رشته‌محل آزاد')

@section('content')
    @php($value = fn (string $key) => old($key, $program->{$key}))
    @if($program->exists && $program->source_type === \App\Models\AzadProgram::SOURCE_BOOKLET)
        <div class="alert alert-light border small">این ردیف از دفترچه {{ $program->booklet_label }} (صفحه {{ \App\Support\PersianDate::number($program->booklet_page) }}) آمده است. اگر ویرایش شود، از این به بعد ثبت دستی حساب می‌شود و بارگذاری دوباره دفترچه آن را تغییر نمی‌دهد.</div>
    @endif

    <form method="post" action="{{ $program->exists ? route('admin.azad-programs.update', $program) : route('admin.azad-programs.store') }}">
        @csrf
        @if($program->exists) @method('PUT') @endif
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2"><label class="form-label">سال</label><input name="year" type="number" value="{{ $value('year') }}" class="form-control ltr" required></div>
                    <div class="col-md-4">
                        <label class="form-label">دفترچه / مقطع</label>
                        <select name="booklet" id="program-booklet" class="form-select" required>
                            <option value="">انتخاب کنید</option>
                            @foreach(\App\Models\AzadProgram::BOOKLETS as $key => [$label, $admission])
                                <option value="{{ $key }}" data-admission="{{ $admission }}" @selected($value('booklet') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">استان</label>
                        <input name="province" id="program-province" list="azad-provinces" value="{{ $value('province') }}" class="form-control" required>
                        <datalist id="azad-provinces">@foreach($provinces as $province)<option value="{{ $province }}">@endforeach</datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">شهر</label>
                        <input name="city" id="program-city" list="azad-cities" value="{{ $value('city') }}" class="form-control" required>
                        <datalist id="azad-cities"></datalist>
                    </div>
                    <div class="col-md-2"><label class="form-label">کد محل دانشگاهی</label><input name="unit_code" value="{{ $value('unit_code') }}" class="form-control ltr" inputmode="numeric" required></div>
                    <div class="col-md-4"><label class="form-label">نام محل دانشگاهی</label><input name="unit_name" value="{{ $value('unit_name') }}" class="form-control" placeholder="مثلاً واحد تبریز" required></div>
                    <div class="col-md-2"><label class="form-label">کد رشته تحصیلی</label><input name="field_code" value="{{ $value('field_code') }}" class="form-control ltr" inputmode="numeric" required></div>
                    <div class="col-md-4"><label class="form-label">نام رشته تحصیلی</label><input name="field_name" value="{{ $value('field_name') }}" class="form-control" required></div>
                    <div class="col-md-3">
                        <label class="form-label">جنس پذیرش</label>
                        <select name="gender" class="form-select" required>
                            @foreach(\App\Models\AzadProgram::GENDERS as $gender)
                                <option value="{{ $gender }}" @selected($value('gender') === $gender)>{{ $gender }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" data-exam-only>
                        <label class="form-label">گروه آزمایشی</label>
                        <select name="exam_group" class="form-select">
                            <option value="">-</option>
                            @foreach(\App\Models\AzadProgram::GROUPS as $key => $label)
                                <option value="{{ $key }}" @selected($value('exam_group') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">گروه آموزشی</label>
                        <select name="education_group" class="form-select">
                            <option value="">-</option>
                            @foreach(\App\Models\AzadProgram::GROUPS as $key => $label)
                                <option value="{{ $key }}" @selected($value('education_group') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">صفحه دفترچه</label><input name="booklet_page" type="number" min="1" value="{{ $value('booklet_page') }}" class="form-control ltr"></div>
                    <div class="col-md-3" data-exam-only><label class="form-label">ظرفیت نیمسال اول</label><input name="capacity_first" type="number" min="0" value="{{ $value('capacity_first') }}" class="form-control ltr" placeholder="خالی = بدون پذیرش"></div>
                    <div class="col-md-3" data-exam-only><label class="form-label">ظرفیت نیمسال دوم</label><input name="capacity_second" type="number" min="0" value="{{ $value('capacity_second') }}" class="form-control ltr" placeholder="خالی = بدون پذیرش"></div>
                    <div class="col-md-12 d-flex flex-wrap gap-4">
                        <div class="form-check"><input type="hidden" name="part_time" value="0"><input class="form-check-input" type="checkbox" name="part_time" value="1" id="part-time" @checked((bool) $value('part_time'))><label class="form-check-label" for="part-time">پاره وقت</label></div>
                        <div class="form-check"><input type="hidden" name="self_funded" value="0"><input class="form-check-input" type="checkbox" name="self_funded" value="1" id="self-funded" @checked((bool) $value('self_funded'))><label class="form-check-label" for="self-funded">ظرفیت خودگردان</label></div>
                        <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is-active" @checked((bool) $value('is_active'))><label class="form-check-label" for="is-active">فعال</label></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="{{ route('admin.azad-programs.index') }}">بازگشت</a>
            <button class="btn btn-primary">ذخیره</button>
        </div>
    </form>

    @if($program->exists)
        @can('delete_azad_programs')
            <form method="post" action="{{ route('admin.azad-programs.destroy', $program) }}" class="mt-3" onsubmit="return confirm('این رشته‌محل حذف شود؟ لیست‌های ثبت‌شده دانش‌آموزان تغییری نمی‌کنند.')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger"><i class="ri-delete-bin-line"></i> حذف رشته‌محل</button>
            </form>
        @endcan
    @endif
@endsection

@push('scripts')
<script>
    (() => {
        const booklet = document.getElementById('program-booklet');
        const province = document.getElementById('program-province');
        const cities = document.getElementById('azad-cities');
        const toggleExam = () => {
            const exam = booklet.selectedOptions[0]?.dataset.admission === 'exam';
            document.querySelectorAll('[data-exam-only]').forEach(el => { el.hidden = !exam; });
        };
        const loadCities = async () => {
            cities.innerHTML = '';
            if (!province.value.trim()) return;
            const response = await fetch(@json(route('admin.azad-programs.cities')) + '?' + new URLSearchParams({ province: province.value.trim() }), { headers: { Accept: 'application/json' } });
            for (const name of await response.json()) cities.append(new Option(name, name));
        };
        booklet.addEventListener('change', toggleExam);
        province.addEventListener('change', loadCities);
        toggleExam();
        loadCities();
    })();
</script>
@endpush

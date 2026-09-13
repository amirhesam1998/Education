@extends('layouts.admin')

@section('title', 'جزئیات رشته‌محل')

@section('actions')
    @can('update_study_programs')<a class="btn btn-primary" href="{{ route('admin.study-programs.edit', $program) }}">ویرایش</a>@endcan
    @can('delete_study_programs')
        @if($program->is_active)<form class="d-inline" method="post" action="{{ route('admin.study-programs.destroy', $program) }}" onsubmit="return confirm('این رشته‌محل ممکن است در انتخاب رشته‌های قبلی استفاده شده باشد. حذف آن فقط از لیست جستجو/انتخاب‌های جدید اثر می‌گذارد و سوابق قبلی حذف نمی‌شوند.')">@csrf @method('DELETE')<button class="btn btn-outline-danger">غیرفعال کردن</button></form>@endif
    @endcan
    <a class="btn btn-outline-secondary" href="{{ route('admin.study-programs.index') }}">بازگشت</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                @foreach([
                    'کدرشته محل' => $program->code,
                    'سال' => $program->examYear?->year,
                    'گروه' => $program->examGroup?->name,
                    'استان' => $program->province?->name,
                    'شهر' => $program->city?->name,
                    'دانشگاه / مؤسسه' => $program->institution?->name,
                    'دانشکده / محل تحصیل' => $program->institutionCampus?->name,
                    'رشته' => $program->academicField?->name,
                    'نوع دوره' => $program->courseType?->name,
                    'دوره درج‌شده در دفترچه' => $program->original_course_type,
                    'نحوه پذیرش' => $program->admissionType?->name,
                    'جنس پذیرش' => collect([$program->accepts_male ? 'مرد' : null, $program->accepts_female ? 'زن' : null])->filter()->implode(' / '),
                    'ظرفیت نیمسال اول' => $program->first_semester_capacity,
                    'ظرفیت نیمسال دوم' => $program->second_semester_capacity,
                    'صفحه دفترچه' => $program->booklet_page,
                    'بخش دفترچه' => $program->booklet_section,
                    'روش تشخیص شهر' => $program->city_detection_method,
                    'فایل منبع' => $program->source_file,
                    'ردیف فایل' => $program->source_row,
                ] as $label => $value)
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small mb-1">{{ $label }}</div>
                            <div class="fw-bold">{{ \App\Support\PersianDate::number($value ?: '-') }}</div>
                        </div>
                    </div>
                @endforeach
                <div class="col-12">
                    <div class="border rounded p-3">
                        <div class="text-muted small mb-1">توضیحات</div>
                        <div>{{ $program->description ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

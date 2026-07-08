@csrf

@if($mode === 'create')
    <div class="mb-3">
        <label class="form-label">نوع ایجاد</label>
        <select name="mode" class="form-select">
            <option value="single" @selected(old('mode', 'single') === 'single')>تک تایم</option>
            <option value="repeat" @selected(old('mode') === 'repeat')>تکرارشونده</option>
        </select>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">مشاور</label>
        <select name="advisor_id" class="form-select" required>
            <option value="">انتخاب کنید</option>
            @foreach($advisors as $advisor)
                <option value="{{ $advisor->id }}" @selected(old('advisor_id', $slot->advisor_id) == $advisor->id)>{{ $advisor->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">ظرفیت</label>
        <input type="number" min="1" name="capacity" value="{{ old('capacity', $slot->capacity ?? 1) }}" class="form-control">
    </div>
    <div class="col-md-3">
        <label class="form-label">وضعیت</label>
        <select name="status" class="form-select">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $slot->status?->value ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-4">
        <label class="form-label">تاریخ</label>
        <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(old('date', $slot->date)) }}" class="form-control jalali-date-picker" autocomplete="off">
    </div>
    <div class="col-md-4">
        <label class="form-label">زمان شروع</label>
        <input type="time" name="start_time" value="{{ old('start_time', $slot->start_time ? substr($slot->start_time, 0, 5) : '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">زمان پایان</label>
        <input type="time" name="end_time" value="{{ old('end_time', $slot->end_time ? substr($slot->end_time, 0, 5) : '') }}" class="form-control">
    </div>
</div>

@if($mode === 'create')
    <hr>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">تاریخ شروع تکرار</label>
            <input type="text" name="repeat_start_date" value="{{ \App\Support\PersianDate::inputDate(old('repeat_start_date')) }}" class="form-control jalali-date-picker" autocomplete="off">
        </div>
        <div class="col-md-3">
            <label class="form-label">تاریخ پایان تکرار</label>
            <input type="text" name="repeat_end_date" value="{{ \App\Support\PersianDate::inputDate(old('repeat_end_date')) }}" class="form-control jalali-date-picker" autocomplete="off">
        </div>
        <div class="col-md-2">
            <label class="form-label">شروع روزانه</label>
            <input type="time" name="daily_start_time" value="{{ old('daily_start_time', '08:00') }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">پایان روزانه</label>
            <input type="time" name="daily_end_time" value="{{ old('daily_end_time', '18:00') }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">فاصله دقیقه</label>
            <input type="number" min="15" name="interval_minutes" value="{{ old('interval_minutes', 60) }}" class="form-control">
        </div>
    </div>
@endif

<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">ذخیره</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}">بازگشت</a>
</div>

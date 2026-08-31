@push('styles')
<style>
    .form-card{ margin-bottom: 1rem; }
    .form-card .card-header{
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .95rem;
    }
    .form-card .card-header i{ color: var(--brand-600); font-size: 1.05rem; }
    .form-card .card-body{ padding: 1.25rem; }

    .form-hint{
        font-size: .76rem;
        color: var(--ink-500);
        margin-top: .3rem;
    }

    #repeatFieldsCard{ display: none; }
    #repeatFieldsCard.is-visible{ display: block; }

    .form-actions{
        position: sticky;
        bottom: 0;
        background: var(--bg);
        padding: .9rem 0 .2rem;
        display: flex;
        gap: .6rem;
        border-top: 1px solid transparent;
        justify-content: end;
    }
    .form-actions .btn{ min-width: 120px; }

    @media (max-width: 575.98px){
        .form-card .card-body{ padding: 1rem; }
        .form-actions{ flex-direction: column-reverse; }
        .form-actions .btn{ width: 100%; }
    }
</style>
@endpush

@csrf

@if($mode === 'create')
    <div class="card form-card">
        <div class="card-header"><i class="ri-git-branch-line"></i> نوع ایجاد</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نوع ایجاد</label>
                    <select name="mode" id="creationMode" class="form-select">
                        <option value="single" @selected(old('mode', 'single') === 'single')>تک تایم</option>
                        <option value="repeat" @selected(old('mode') === 'repeat')>تکرارشونده</option>
                    </select>
                    <div class="form-hint">در حالت «تکرارشونده» می‌توانید یک بازه تاریخ و الگوی روزانه تعریف کنید.</div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card form-card">
    <div class="card-header"><i class="ri-user-settings-line"></i> اطلاعات پایه</div>
    <div class="card-body">
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
                <label class="form-label">مدت هر رزرو</label>
                <div class="input-group">
                    <input type="number" min="5" max="240" name="duration_minutes" value="{{ old('duration_minutes', $slot->duration_minutes ?? 15) }}" class="form-control">
                    <span class="input-group-text">دقیقه</span>
                </div>
                <div class="form-hint">این مدت فقط برای همین تایم استفاده می‌شود و بازه‌های قابل رزرو را می‌سازد.</div>
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
    </div>
</div>

<div class="card form-card">
    <div class="card-header"><i class="ri-calendar-2-line"></i> زمان‌بندی</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">تاریخ</label>
                <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(old('date', $slot->date)) }}" class="form-control jalali-date-picker" autocomplete="off" placeholder="انتخاب تاریخ">
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
    </div>
</div>

@if($mode === 'create')
    <div class="card form-card" id="repeatFieldsCard">
        <div class="card-header"><i class="ri-repeat-line"></i> تنظیمات تکرار</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">تاریخ شروع تکرار</label>
                    <input type="text" name="repeat_start_date" value="{{ \App\Support\PersianDate::inputDate(old('repeat_start_date')) }}" class="form-control jalali-date-picker" autocomplete="off">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">تاریخ پایان تکرار</label>
                    <input type="text" name="repeat_end_date" value="{{ \App\Support\PersianDate::inputDate(old('repeat_end_date')) }}" class="form-control jalali-date-picker" autocomplete="off">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">شروع روزانه</label>
                    <input type="time" name="daily_start_time" value="{{ old('daily_start_time', '08:00') }}" class="form-control">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">پایان روزانه</label>
                    <input type="time" name="daily_end_time" value="{{ old('daily_end_time', '18:00') }}" class="form-control">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label">فاصله دقیقه</label>
                    <input type="number" min="15" name="interval_minutes" value="{{ old('interval_minutes', 60) }}" class="form-control">
                </div>
            </div>
            <div class="form-hint">با این تنظیمات، به‌صورت خودکار یک تایم در هر بازه زمانی مشخص‌شده، در طول محدوده تاریخ انتخابی ایجاد می‌شود.</div>
        </div>
    </div>
@endif

<div class="form-actions">
    <button class="btn btn-primary">
        <i class="ri-save-line align-middle"></i> ذخیره
    </button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}">انصراف</a>
</div>

@if($mode === 'create')
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modeSelect = document.getElementById('creationMode');
            const repeatCard = document.getElementById('repeatFieldsCard');

            function syncRepeatVisibility () {
                if (!modeSelect || !repeatCard) return;
                repeatCard.classList.toggle('is-visible', modeSelect.value === 'repeat');
            }

            modeSelect?.addEventListener('change', syncRepeatVisibility);
            syncRepeatVisibility();
        });
    </script>
    @endpush
@endif

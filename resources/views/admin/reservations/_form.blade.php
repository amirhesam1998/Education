@csrf
@php
    $student = $reservation->student;
    $phones = $student?->phones ?? collect();
    $phoneOne = old('phone_one', $phones->firstWhere('is_primary', true)?->phone ?? $phones->get(0)?->phone);
    $phoneTwo = old('phone_two', $phones->where('is_primary', false)->first()?->phone ?? $phones->get(1)?->phone);
    $selectedSlotId = old('slot_id', $reservation->slot_id);
    $selectedInterval = old('reservation_interval', ($reservation->assignedStartTime() && $reservation->assignedEndTime()) ? substr($reservation->assignedStartTime(), 0, 5).'|'.substr($reservation->assignedEndTime(), 0, 5) : '');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">نام و نام خانوادگی</label>
        <input name="full_name" value="{{ old('full_name', $student?->full_name) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">رشته</label>
        <input name="major" value="{{ old('major', $student?->major) }}" class="form-control" list="majors">
        <datalist id="majors">
            @foreach($majors as $major)
                <option value="{{ $major }}">
            @endforeach
        </datalist>
    </div>
    <div class="col-md-4">
        <label class="form-label">تراز</label>
        <input name="score" value="{{ old('score', $student?->score) }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">نوع کنکور</label>
        <input name="exam_type" value="{{ old('exam_type', $student?->exam_type) }}" class="form-control" list="exam-types">
        <datalist id="exam-types">
            @foreach($examTypes as $examType)
                <option value="{{ $examType }}">
            @endforeach
        </datalist>
    </div>
    <div class="col-md-4">
        <label class="form-label">شماره تماس اول</label>
        <input name="phone_one" value="{{ $phoneOne }}" class="form-control ltr" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">شماره تماس دوم</label>
        <input name="phone_two" value="{{ $phoneTwo }}" class="form-control ltr">
    </div>

    @if($mode === 'create')
        <div class="col-md-8">
            <label class="form-label">تایم رزرو</label>
            <select name="slot_id" id="slot_id" class="form-select" required>
                <option value="">انتخاب کنید</option>
                @foreach($availableSlots as $slot)
                    @php($hasAvailableInterval = collect($slotIntervals[$slot->id] ?? [])->contains('available', true))
                    <option value="{{ $slot->id }}" data-general-range="{{ \App\Support\PersianDate::time($slot->start_time) }} تا {{ \App\Support\PersianDate::time($slot->end_time) }}" @selected($selectedSlotId == $slot->id) @disabled(! $hasAvailableInterval && $selectedSlotId != $slot->id)>
                        {{ \App\Support\PersianDate::date($slot->date) }}
                        - {{ \App\Support\PersianDate::time($slot->start_time) }} تا {{ \App\Support\PersianDate::time($slot->end_time) }}
                        - {{ $slot->advisor?->name }}
                        @unless($hasAvailableInterval)
                            - بدون بازه آزاد
                        @endunless
                    </option>
                @endforeach
            </select>
        </div>
    @else
        <div class="col-md-8">
            <label class="form-label">بازه کلی تایم</label>
            <div class="form-control bg-light">
                {{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date).' - '.\App\Support\PersianDate::time($reservation->slot->start_time).' تا '.\App\Support\PersianDate::time($reservation->slot->end_time).' - '.$reservation->slot->advisor?->name : '-' }}
            </div>
            <input type="hidden" id="slot_id" value="{{ $reservation->slot_id }}" data-general-range="{{ $reservation->slot ? \App\Support\PersianDate::time($reservation->slot->start_time).' تا '.\App\Support\PersianDate::time($reservation->slot->end_time) : '-' }}">
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label">زمان اختصاص داده شده</label>
        <select name="reservation_interval" id="reservation_interval" class="form-select" required data-selected="{{ $selectedInterval }}">
            <option value="">ابتدا تایم را انتخاب کنید</option>
        </select>
    </div>
    <div class="col-12">
        <div class="alert alert-info mb-0" id="reservation-interval-summary">
            <div><strong>بازه کلی تایم:</strong> <span data-general-range>-</span></div>
            <div><strong>زمان اختصاص داده شده به این رزرو:</strong> <span data-assigned-range>-</span></div>
        </div>
    </div>
</div>

<hr>

<div class="row g-3">
    <div class="col-md-4">
        <div class="form-check mt-4">
            <input type="checkbox" name="prepayment_required" value="1" class="form-check-input" id="prepayment_required"
                @checked(old('prepayment_required', $reservation->prepayment_required))>
            <label class="form-check-label" for="prepayment_required">نیاز به پیش پرداخت</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">مبلغ پیش پرداخت</label>
        <input type="number" name="prepayment_amount" value="{{ old('prepayment_amount', $reservation->prepayment_amount ?? $defaultPrepaymentAmount ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">مهلت پرداخت</label>
        <input type="text" name="payment_deadline_at" value="{{ \App\Support\PersianDate::inputDateTime(old('payment_deadline_at', $reservation->payment_deadline_at ?? now()->addHours($defaultDeadlineHours ?? 24))) }}" class="form-control jalali-datetime-picker" autocomplete="off">
    </div>
    <div class="col-12">
        <label class="form-label">یادداشت داخلی</label>
        <textarea name="admin_note" rows="3" class="form-control">{{ old('admin_note', $reservation->admin_note) }}</textarea>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">ذخیره</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.index') }}">بازگشت</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slotIntervals = @json($slotIntervals ?? []);
        const slotSelect = document.getElementById('slot_id');
        const intervalSelect = document.getElementById('reservation_interval');
        const summary = document.getElementById('reservation-interval-summary');
        const selectedInterval = intervalSelect?.dataset.selected || '';

        if (!slotSelect || !intervalSelect || !summary) {
            return;
        }

        const faNumber = (value) => String(value || '').replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
        const formatRange = (range) => faNumber(String(range || '').replace('|', ' تا '));

        function selectedSlotOption() {
            return slotSelect.tagName === 'SELECT'
                ? slotSelect.options[slotSelect.selectedIndex]
                : null;
        }

        function generalRange(slotId) {
            const option = selectedSlotOption();

            if (slotSelect.dataset.generalRange) {
                return slotSelect.dataset.generalRange;
            }

            if (option?.dataset.generalRange) {
                return option.dataset.generalRange;
            }

            const intervals = slotIntervals[slotId] || [];

            if (!intervals.length) {
                return '-';
            }

            return '-';
        }

        function renderIntervals() {
            const slotId = slotSelect.value;
            const intervals = slotIntervals[slotId] || [];
            const currentSelected = intervalSelect.value || selectedInterval;
            intervalSelect.innerHTML = '';

            if (!slotId || intervals.length === 0) {
                intervalSelect.add(new Option('ابتدا تایم را انتخاب کنید', ''));
                updateSummary();

                return;
            }

            intervals.forEach((interval) => {
                const label = `${formatRange(interval.label)}${interval.available ? '' : ` - ${interval.status_label}`}`;
                const option = new Option(label, interval.value);
                option.disabled = !interval.available && interval.value !== selectedInterval;
                option.selected = interval.value === currentSelected;
                intervalSelect.add(option);
            });

            if (!intervalSelect.value) {
                const firstAvailable = intervals.find((interval) => interval.available);

                if (firstAvailable) {
                    intervalSelect.value = firstAvailable.value;
                }
            }

            updateSummary();
        }

        function updateSummary() {
            const slotId = slotSelect.value;
            summary.querySelector('[data-general-range]').textContent = generalRange(slotId);
            summary.querySelector('[data-assigned-range]').textContent = intervalSelect.value ? formatRange(intervalSelect.value) : '-';
        }

        slotSelect.addEventListener('change', renderIntervals);
        intervalSelect.addEventListener('change', updateSummary);
        renderIntervals();
    });
</script>

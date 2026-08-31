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

    .form-control.bg-light{
        background: var(--bg) !important;
        color: var(--ink-700);
        font-size: .86rem;
    }

    #reservation-interval-summary{
        background: #e5eefb;
        border: none;
        color: var(--info);
        border-radius: var(--radius-md);
        font-size: .84rem;
        padding: .9rem 1rem;
    }
    #reservation-interval-summary strong{ color: var(--ink-900); }
    #reservation-interval-summary > div + div{ margin-top: .3rem; }

    .form-actions{
        position: sticky;
        bottom: 0;
        background: var(--bg);
        padding: .9rem 0 .2rem;
        display: flex;
        gap: .6rem;
        justify-content: end;
    }
    .form-actions .btn{ min-width: 120px; }

    .reservation-modal-backdrop{
        position: fixed;
        inset: 0;
        z-index: 1060;
        background: rgba(18, 24, 32, .58);
        backdrop-filter: blur(9px);
        display: grid;
        place-items: center;
        padding: 1rem;
    }
    .reservation-modal{
        width: min(1120px, 100%);
        max-height: calc(100vh - 2rem);
        overflow: hidden;
        background: var(--surface);
        border-radius: var(--radius-lg);
        box-shadow: 0 24px 80px rgba(20, 30, 40, .24);
        display: flex;
        flex-direction: column;
    }
    .reservation-modal-header{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
    }
    .reservation-modal-title{
        display: flex;
        align-items: center;
        gap: .5rem;
        font-weight: 800;
        color: var(--ink-900);
    }
    .reservation-modal-close{
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        color: var(--ink-700);
        background: var(--surface);
    }
    .reservation-modal-body{
        overflow: auto;
        padding: 1rem 1.25rem 0;
        background: var(--bg);
    }
    .appointment-picker{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1rem;
        background: var(--surface);
    }
    .appointment-advisor{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .85rem;
        color: var(--ink-700);
        font-size: .85rem;
    }
    .date-card-row{
        display: flex;
        gap: .6rem;
        overflow-x: auto;
        padding-bottom: .35rem;
        margin-bottom: .9rem;
    }
    .date-card{
        flex: 0 0 150px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--surface);
        padding: .75rem;
        text-align: right;
        color: var(--ink-700);
    }
    .date-card strong{
        display: block;
        color: var(--ink-900);
        margin: .15rem 0;
    }
    .date-card small{ display: block; color: var(--ink-500); }
    .date-card.is-active{
        border-color: var(--brand-500);
        background: var(--brand-50);
        box-shadow: 0 0 0 3px rgba(47, 143, 131, .1);
    }
    .date-card:disabled{
        opacity: .55;
        cursor: not-allowed;
        background: var(--ink-100);
    }
    .time-chip-list{
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        min-height: 42px;
    }
    .time-chip{
        border: 1px solid var(--border);
        border-radius: 999px;
        background: var(--surface);
        color: var(--ink-700);
        padding: .48rem .85rem;
        font-size: .83rem;
    }
    .time-chip.is-active{
        border-color: var(--brand-500);
        background: var(--brand-500);
        color: #fff;
    }
    .time-chip:disabled{
        opacity: .52;
        cursor: not-allowed;
        text-decoration: line-through;
    }
    .appointment-empty{
        display: none;
        color: var(--warning);
        font-size: .84rem;
        margin-top: .75rem;
    }
    .appointment-native-control{ display: none; }

    @media (max-width: 575.98px){
        .reservation-modal-backdrop{ padding: 0; place-items: stretch; }
        .reservation-modal{ max-height: 100vh; border-radius: 0; }
        .reservation-modal-body{ padding: .85rem .85rem 0; }
        .date-card{ flex-basis: 132px; }
        .form-card .card-body{ padding: 1rem; }
        .form-actions{ flex-direction: column-reverse; }
        .form-actions .btn{ width: 100%; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateGroups = @json($slotDateGroups ?? []);
        const slotSelect = document.getElementById('slot_id');
        const intervalSelect = document.getElementById('reservation_interval');
        const summary = document.getElementById('reservation-interval-summary');
        const cards = Array.from(document.querySelectorAll('[data-date-card]'));
        const chipList = document.querySelector('[data-time-chips]');
        const emptyState = document.querySelector('[data-appointment-empty]');
        const firstAvailableButton = document.querySelector('[data-first-available]');
        const advisorLabel = document.querySelector('[data-selected-advisor]');
        const durationLabel = document.querySelector('[data-selected-duration]');
        const selectedInterval = intervalSelect?.dataset.selected || '';

        if (!dateGroups.length || !slotSelect || !intervalSelect || !summary || !chipList) {
            return;
        }

        const groupsByDate = Object.fromEntries(dateGroups.map((group) => [group.date, group]));
        const selectedGroup = dateGroups.find((group) => group.intervals.some((interval) => String(interval.slot_id) === String(slotSelect.value)))
            || dateGroups[0];
        const faNumber = (value) => String(value || '').replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
        const formatDuration = (minutes) => minutes ? `${faNumber(minutes)} دقیقه` : '-';

        function syncFields(interval) {
            intervalSelect.innerHTML = '';

            groupsByDate[interval.date]?.intervals?.forEach((candidate) => {
                const option = new Option(candidate.label, candidate.value);
                option.disabled = !candidate.available && candidate.value !== selectedInterval;
                intervalSelect.add(option);
            });

            slotSelect.value = interval.slot_id;
            intervalSelect.value = interval.value;
            summary.querySelector('[data-general-range]').textContent = interval.slot_range || '-';
            summary.querySelector('[data-assigned-range]').textContent = interval.label || '-';
            if (durationLabel) {
                durationLabel.textContent = formatDuration(interval.duration_minutes);
            }
        }

        function renderDate(dateKey) {
            const group = groupsByDate[dateKey];

            if (!group) {
                return;
            }

            cards.forEach((card) => card.classList.toggle('is-active', card.dataset.dateKey === dateKey));
            advisorLabel.textContent = group.advisor_label || 'همه مشاوران';
            chipList.innerHTML = '';

            const current = group.intervals.find((interval) => String(interval.slot_id) === String(slotSelect.value) && interval.value === (intervalSelect.value || selectedInterval));
            const initial = current || group.intervals.find((interval) => interval.available) || group.intervals[0];

            group.intervals.forEach((interval) => {
                interval.date = group.date;
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'time-chip';
                chip.textContent = `${interval.label}${interval.available ? '' : ` - ${interval.status_label}`}`;
                chip.disabled = !interval.available && interval.value !== selectedInterval;
                chip.classList.toggle('is-active', initial && String(interval.slot_id) === String(initial.slot_id) && interval.value === initial.value);
                chip.addEventListener('click', () => {
                    syncFields(interval);
                    renderDate(group.date);
                });
                chipList.appendChild(chip);
            });

            if (initial) {
                syncFields(initial);
            }

            if (emptyState) {
                emptyState.style.display = group.intervals.some((interval) => interval.available) ? 'none' : 'block';
            }
        }

        cards.forEach((card) => card.addEventListener('click', () => renderDate(card.dataset.dateKey)));
        firstAvailableButton?.addEventListener('click', () => {
            const group = dateGroups.find((candidate) => candidate.available_count > 0);

            if (group) {
                renderDate(group.date);
                document.querySelector(`[data-date-key="${group.date}"]`)?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
        });

        renderDate(selectedGroup.date);
    });
</script>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slotIntervals = @json($slotIntervals ?? []);
        const slotSelect = document.getElementById('slot_id');
        const intervalSelect = document.getElementById('reservation_interval');
        const summary = document.getElementById('reservation-interval-summary');
        const cards = Array.from(document.querySelectorAll('[data-slot-card]'));
        const chipList = document.querySelector('[data-time-chips]');
        const emptyState = document.querySelector('[data-appointment-empty]');
        const firstAvailableButton = document.querySelector('[data-first-available]');
        const advisorLabel = document.querySelector('[data-selected-advisor]');
        const selectedInterval = intervalSelect?.dataset.selected || '';

        if (!slotSelect || !intervalSelect || !summary || !chipList || !cards.length) {
            return;
        }

        const faNumber = (value) => String(value || '').replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
        const formatRange = (range) => faNumber(String(range || '').replace('|', ' تا '));
        const selectedCard = () => cards.find((card) => card.dataset.slotId === slotSelect.value) || null;

        function setSlot(slotId) {
            slotSelect.value = slotId;
            delete slotSelect.dataset.generalRange;
            slotSelect.dispatchEvent(new Event('change'));
            renderPicker();
        }

        function renderPicker() {
            const intervals = slotIntervals[slotSelect.value] || [];
            const card = selectedCard();

            cards.forEach((candidate) => {
                candidate.classList.toggle('is-active', candidate === card);
            });

            if (card) {
                advisorLabel.textContent = `${card.dataset.advisor} | ${card.dataset.range}`;
                summary.querySelector('[data-general-range]').textContent = card.dataset.range;
            }

            chipList.innerHTML = '';

            intervals.forEach((interval) => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'time-chip';
                chip.textContent = `${formatRange(interval.value)}${interval.available ? '' : ` - ${interval.status_label}`}`;
                chip.disabled = !interval.available && interval.value !== selectedInterval;
                chip.classList.toggle('is-active', interval.value === intervalSelect.value);
                chip.addEventListener('click', () => {
                    intervalSelect.value = interval.value;
                    intervalSelect.dispatchEvent(new Event('change'));
                    renderPicker();
                });
                chipList.appendChild(chip);
            });

            if (emptyState) {
                emptyState.style.display = intervals.some((interval) => interval.available) ? 'none' : 'block';
            }
        }

        cards.forEach((card) => card.addEventListener('click', () => setSlot(card.dataset.slotId)));
        firstAvailableButton?.addEventListener('click', () => {
            const card = cards.find((candidate) => !candidate.disabled);

            if (card) {
                setSlot(card.dataset.slotId);
                card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
        });
        slotSelect.addEventListener('change', () => setTimeout(renderPicker));
        intervalSelect.addEventListener('change', () => setTimeout(renderPicker));

        if (!slotSelect.value) {
            const firstCard = cards[0];

            if (firstCard) {
                setSlot(firstCard.dataset.slotId);
            }
        }

        setTimeout(renderPicker);
    });
</script>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const amountInput = document.getElementById('prepayment_amount');

        document.querySelectorAll('[data-prepayment-amount]').forEach((button) => {
            button.addEventListener('click', () => {
                if (amountInput) {
                    amountInput.value = button.dataset.prepaymentAmount;
                }
            });
        });

        const requiredInput = document.getElementById('prepayment_required');
        const prepaymentFields = Array.from(document.querySelectorAll('[data-prepayment-field]'));

        function setPrepaymentFieldsEnabled(enabled, restoreDefaults = false) {
            prepaymentFields.forEach((field) => {
                field.classList.toggle('d-none', !enabled);

                field.querySelectorAll('input, select, textarea, button').forEach((control) => {
                    control.disabled = !enabled;

                    if (!enabled && 'value' in control) {
                        control.value = '';
                    }
                });
            });

            if (enabled && restoreDefaults) {
                prepaymentFields.forEach((field) => {
                    field.querySelectorAll('[data-default-value]').forEach((control) => {
                        if (!control.value) {
                            control.value = control.dataset.defaultValue || '';
                        }
                    });
                });
            }
        }

        if (requiredInput) {
            requiredInput.addEventListener('change', () => setPrepaymentFieldsEnabled(requiredInput.checked, true));
            setPrepaymentFieldsEnabled(requiredInput.checked);
        }
    });
</script>
@endpush

@csrf
@php
    $student = $reservation->student;
    $phones = $student?->phones ?? collect();
    $phoneOne = old('phone_one', $phones->firstWhere('is_primary', true)?->phone ?? $phones->get(0)?->phone);
    $phoneTwo = old('phone_two', $phones->where('is_primary', false)->first()?->phone ?? $phones->get(1)?->phone);
    $selectedSlotId = old('slot_id', request('slot_id', $reservation->slot_id));
    $selectedInterval = old('reservation_interval', request('reservation_interval', ($reservation->assignedStartTime() && $reservation->assignedEndTime()) ? substr($reservation->assignedStartTime(), 0, 5).'|'.substr($reservation->assignedEndTime(), 0, 5) : ''));
    $selectedExamTypes = old('exam_type', is_array($student?->exam_type) ? $student->exam_type : array_filter([(string) $student?->exam_type]));
    $closeUrl = $reservation->exists ? route('admin.reservations.show', $reservation) : route('admin.reservations.index');
    $weekdays = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
@endphp

<div class="reservation-modal-backdrop" data-reservation-modal>
    <div class="reservation-modal" role="dialog" aria-modal="true" aria-labelledby="reservation-modal-title">
        <div class="reservation-modal-header">
            <div class="reservation-modal-title" id="reservation-modal-title">
                <i class="ri-calendar-check-line"></i>
                انتخاب نوبت
            </div>
            <a class="reservation-modal-close" href="{{ $closeUrl }}" aria-label="بستن">
                <i class="ri-close-line"></i>
            </a>
        </div>
        <div class="reservation-modal-body">

<div class="card form-card">
    <div class="card-header"><i class="ri-graduation-cap-line"></i> اطلاعات دانش آموز</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی</label>
                <input name="full_name" value="{{ old('full_name', $student?->full_name) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">رشته</label>
                <select name="major" class="form-select">
                    <option value="">انتخاب کنید</option>
                    @foreach($majors as $major)
                        <option value="{{ $major }}" @selected(old('major', $student?->major) === $major)>{{ $major }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">تراز</label>
                <input name="score" value="{{ old('score', $student?->score) }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">نوع کنکور</label>
                <select name="exam_type[]" class="form-select" multiple size="4">
                    @foreach($examTypes as $examType)
                        <option value="{{ $examType }}" @selected(in_array($examType, $selectedExamTypes, true))>{{ $examType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">شماره تماس اول</label>
                <input name="phone_one" value="{{ $phoneOne }}" class="form-control ltr" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">شماره تماس دوم</label>
                <input name="phone_two" value="{{ $phoneTwo }}" class="form-control ltr">
            </div>
        </div>
    </div>
</div>

<div class="card form-card">
    <div class="card-header"><i class="ri-calendar-2-line"></i> زمان‌بندی رزرو</div>
    <div class="card-body">
        <div class="appointment-picker mb-3">
            <div class="appointment-advisor">
                <div>
                    <i class="ri-user-star-line align-middle"></i>
                    <span data-selected-advisor>مشاور را از روی نوبت انتخاب کنید</span>
                </div>
                <span data-selected-duration>-</span>
            </div>

            <div class="date-card-row" data-date-cards>
                @foreach($slotDateGroups ?? [] as $dateGroup)
                    <button
                        type="button"
                        class="date-card"
                        data-date-card
                        data-date-key="{{ $dateGroup['date'] }}"
                        data-advisor="{{ $dateGroup['advisor_label'] ?: '-' }}"
                        @disabled(($dateGroup['available_count'] ?? 0) === 0 && ! collect($dateGroup['intervals'])->contains('slot_id', (int) $selectedSlotId))
                    >
                        <span>{{ $dateGroup['weekday_label'] }}</span>
                        <strong>{{ $dateGroup['jalali_date'] }}</strong>
                        <small>{{ $dateGroup['advisor_label'] ?: '-' }}</small>
                        <small>{{ \App\Support\PersianDate::number($dateGroup['available_count']) }} نوبت آزاد</small>
                    </button>
                @endforeach
            </div>

            <div class="time-chip-list" data-time-chips></div>
            <div class="appointment-empty" data-appointment-empty>
                در حال حاضر برای این روز نوبت آزادی وجود ندارد.
                <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-first-available>برو به اولین نوبت آزاد بعدی</button>
            </div>
        </div>

        <div class="row g-3">
            @if($mode === 'create')
                <div class="col-md-8 appointment-native-control">
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
                <div class="col-md-8 appointment-native-control">
                    <label class="form-label">بازه کلی تایم</label>
                    <div class="form-control bg-light">
                        {{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date).' - '.\App\Support\PersianDate::time($reservation->slot->start_time).' تا '.\App\Support\PersianDate::time($reservation->slot->end_time).' - '.$reservation->slot->advisor?->name : '-' }}
                    </div>
                    <input type="hidden" name="slot_id" id="slot_id" value="{{ $selectedSlotId }}" data-general-range="{{ $reservation->slot ? \App\Support\PersianDate::time($reservation->slot->start_time).' تا '.\App\Support\PersianDate::time($reservation->slot->end_time) : '-' }}">
                </div>
            @endif

            <div class="col-md-4 appointment-native-control">
                <label class="form-label">زمان اختصاص داده شده</label>
                <select name="reservation_interval" id="reservation_interval" class="form-select" required data-selected="{{ $selectedInterval }}">
                    <option value="">ابتدا تایم را انتخاب کنید</option>
                </select>
            </div>

            <div class="col-12">
                <div id="reservation-interval-summary">
                    <div><strong>بازه کلی تایم:</strong> <span data-general-range>-</span></div>
                    <div><strong>زمان اختصاص داده شده به این رزرو:</strong> <span data-assigned-range>-</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card form-card">
    <div class="card-header"><i class="ri-bank-card-line"></i> پیش پرداخت</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input type="checkbox" name="prepayment_required" value="1" class="form-check-input" id="prepayment_required"
                        @checked(old('prepayment_required', $reservation->prepayment_required))>
                    <label class="form-check-label" for="prepayment_required">نیاز به پیش پرداخت</label>
                </div>
            </div>
            <div class="col-md-4" data-prepayment-field>
                <label class="form-label">مبلغ پیش پرداخت</label>
                <input type="number" name="prepayment_amount" id="prepayment_amount" value="{{ old('prepayment_amount', $reservation->prepayment_amount ?? $defaultPrepaymentAmount ?? '') }}" data-default-value="{{ $reservation->prepayment_amount ?? $defaultPrepaymentAmount ?? '' }}" class="form-control">
                @if(! empty($prepaymentPresets))
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($prepaymentPresets as $preset)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-prepayment-amount="{{ $preset['amount'] }}">
                                {{ $preset['label'] ?: \App\Support\PersianDate::money($preset['amount']) }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="col-md-4" data-prepayment-field>
                <label class="form-label">شماره کارت پرداخت</label>
                <select name="payment_card_id" class="form-select">
                    <option value="">انتخاب کارت پرداخت</option>
                    @foreach($paymentCards ?? [] as $card)
                        <option value="{{ $card->id }}" @selected(old('payment_card_id', $reservation->payment_card_id) == $card->id)>
                            {{ $card->bank_name }} - {{ $card->holder_name }} - {{ $card->formattedNumber() }}
                        </option>
                    @endforeach
                </select>
                @if(($reservation->prepayment_required || old('prepayment_required')) && blank(old('payment_card_id', $reservation->payment_card_id)))
                    <div class="form-hint text-danger">برای رزرو دارای پیش‌پرداخت، کارت پرداخت را انتخاب کنید.</div>
                @endif
            </div>
            <div class="col-md-4" data-prepayment-field>
                <label class="form-label">مهلت پرداخت</label>
                <input type="text" name="payment_deadline_at" value="{{ \App\Support\PersianDate::inputDateTime(old('payment_deadline_at', $reservation->payment_deadline_at ?? now()->addHours($defaultDeadlineHours ?? 24))) }}" data-default-value="{{ \App\Support\PersianDate::inputDateTime($reservation->payment_deadline_at ?? now()->addHours($defaultDeadlineHours ?? 24)) }}" class="form-control jalali-datetime-picker" autocomplete="off">
            </div>
        </div>
    </div>
</div>

<div class="card form-card">
    <div class="card-header"><i class="ri-sticky-note-line"></i> یادداشت داخلی</div>
    <div class="card-body">
        <textarea name="admin_note" rows="3" class="form-control">{{ old('admin_note', $reservation->admin_note) }}</textarea>
        <div class="form-hint">این یادداشت فقط برای کاربران پنل مدیریت قابل مشاهده است.</div>
    </div>
</div>

<div class="form-actions">
    <button class="btn btn-primary">
        <i class="ri-save-line align-middle"></i> ذخیره
    </button>
    <a class="btn btn-outline-secondary" href="{{ $closeUrl }}">بازگشت</a>
</div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slotIntervals = @json($slotIntervals ?? []);
        const slotSelect = document.getElementById('slot_id');
        const intervalSelect = document.getElementById('reservation_interval');
        const summary = document.getElementById('reservation-interval-summary');
        const durationLabel = document.querySelector('[data-selected-duration]');
        const selectedInterval = intervalSelect?.dataset.selected || '';

        if (!slotSelect || !intervalSelect || !summary || document.querySelector('[data-date-card]')) {
            return;
        }

        const faNumber = (value) => String(value || '').replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
        const formatRange = (range) => faNumber(String(range || '').replace('|', ' تا '));
        const formatDuration = (minutes) => minutes ? `${faNumber(minutes)} دقیقه` : '-';

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
            const intervals = slotIntervals[slotId] || [];
            const current = intervals.find((interval) => interval.value === intervalSelect.value) || intervals[0];
            summary.querySelector('[data-general-range]').textContent = generalRange(slotId);
            summary.querySelector('[data-assigned-range]').textContent = intervalSelect.value ? formatRange(intervalSelect.value) : '-';
            if (durationLabel) {
                durationLabel.textContent = formatDuration(current?.duration_minutes);
            }
        }

        slotSelect.addEventListener('change', renderIntervals);
        intervalSelect.addEventListener('change', updateSummary);
        renderIntervals();
    });
</script>
@endpush

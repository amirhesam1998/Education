@extends('layouts.admin')

@section('title', 'جزئیات رزرو')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.index') }}">
        <i class="ri-arrow-right-line align-middle"></i> بازگشت
    </a>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slotIntervals = @json($slotIntervals ?? []);
        const slotSelect = document.getElementById('change_slot_id');
        const intervalSelect = document.getElementById('change_reservation_interval');
        const selectedInterval = intervalSelect?.dataset.selected || '';

        if (!slotSelect || !intervalSelect) {
            return;
        }

        const faNumber = (value) => String(value || '').replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);

        function renderIntervals() {
            const intervals = slotIntervals[slotSelect.value] || [];
            const currentSelected = intervalSelect.value || selectedInterval;
            intervalSelect.innerHTML = '';

            if (!intervals.length) {
                intervalSelect.add(new Option('ابتدا تایم را انتخاب کنید', ''));
                return;
            }

            intervals.forEach((interval) => {
                const label = `${faNumber(interval.value.replace('|', ' تا '))}${interval.available ? '' : ` - ${interval.status_label}`}`;
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
        }

        slotSelect.addEventListener('change', renderIntervals);
        renderIntervals();
    });
</script>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateGroups = @json($slotDateGroups ?? []);
        const slotSelect = document.getElementById('change_slot_id');
        const intervalSelect = document.getElementById('change_reservation_interval');
        const cards = Array.from(document.querySelectorAll('[data-change-date-card]'));
        const selectedInterval = intervalSelect?.dataset.selected || '';

        if (!dateGroups.length || !slotSelect || !intervalSelect || !cards.length) {
            return;
        }

        const groupsByDate = Object.fromEntries(dateGroups.map((group) => [group.date, group]));
        const selectedGroup = dateGroups.find((group) => group.intervals.some((interval) => String(interval.slot_id) === String(slotSelect.value)))
            || dateGroups[0];

        function renderDate(dateKey) {
            const group = groupsByDate[dateKey];

            if (!group) {
                return;
            }

            cards.forEach((card) => card.classList.toggle('is-active', card.dataset.dateKey === dateKey));
            intervalSelect.innerHTML = '';

            group.intervals.forEach((interval) => {
                const option = new Option(`${interval.label}${interval.available ? '' : ` - ${interval.status_label}`}`, interval.value);
                option.dataset.slotId = interval.slot_id;
                option.disabled = !interval.available && interval.value !== selectedInterval;
                intervalSelect.add(option);
            });

            const current = group.intervals.find((interval) => String(interval.slot_id) === String(slotSelect.value) && interval.value === (intervalSelect.value || selectedInterval));
            const initial = current || group.intervals.find((interval) => interval.available) || group.intervals[0];

            if (initial) {
                slotSelect.value = initial.slot_id;
                intervalSelect.value = initial.value;
            }
        }

        cards.forEach((card) => card.addEventListener('click', () => renderDate(card.dataset.dateKey)));
        intervalSelect.addEventListener('change', () => {
            const option = intervalSelect.selectedOptions[0];

            if (option?.dataset.slotId) {
                slotSelect.value = option.dataset.slotId;
            }
        });
        renderDate(selectedGroup.date);
    });
</script>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateGroups = @json($followUpSlotDateGroups ?? []);
        const slotInput = document.getElementById('follow_up_slot_id');
        const intervalInput = document.getElementById('follow_up_reservation_interval');
        const summary = document.getElementById('follow-up-interval-summary');
        const cards = Array.from(document.querySelectorAll('[data-follow-up-date-card]'));
        const chipList = document.querySelector('[data-follow-up-time-chips]');
        const selectedInterval = intervalInput?.dataset.selected || '';

        if (!dateGroups.length || !slotInput || !intervalInput || !summary || !cards.length || !chipList) {
            return;
        }

        const groupsByDate = Object.fromEntries(dateGroups.map((group) => [group.date, group]));
        const selectedGroup = dateGroups.find((group) => group.intervals.some((interval) => String(interval.slot_id) === String(slotInput.value)))
            || dateGroups[0];

        function syncFields(interval) {
            slotInput.value = interval.slot_id;
            intervalInput.value = interval.value;
            summary.querySelector('[data-follow-up-date]').textContent = groupsByDate[interval.date]?.jalali_date || '-';
            summary.querySelector('[data-follow-up-range]').textContent = interval.label || '-';
            summary.querySelector('[data-follow-up-slot-range]').textContent = interval.slot_range || '-';
        }

        function renderDate(dateKey) {
            const group = groupsByDate[dateKey];

            if (!group) {
                return;
            }

            cards.forEach((card) => card.classList.toggle('is-active', card.dataset.dateKey === dateKey));
            chipList.innerHTML = '';

            const current = group.intervals.find((interval) => String(interval.slot_id) === String(slotInput.value) && interval.value === (intervalInput.value || selectedInterval));
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
        }

        cards.forEach((card) => card.addEventListener('click', () => renderDate(card.dataset.dateKey)));
        renderDate(selectedGroup.date);
    });
</script>
@endpush

@push('styles')
<style>
    /* ===========================================================
       Reservation detail — page-local styles
    =========================================================== */
    .card-section{ margin-bottom: 1rem; }
    .card-section .card-header{
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .95rem;
    }
    .card-section .card-header i{ color: var(--brand-600); font-size: 1.05rem; }
    .card-section .card-body{ padding: 1.25rem; }

    .info-grid{
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .85rem;
    }
    .info-item{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .8rem .95rem;
        background: var(--bg);
    }
    .info-label{
        font-size: .74rem;
        color: var(--ink-500);
        margin-bottom: .2rem;
    }
    .info-value{
        font-size: .87rem;
        font-weight: 600;
        color: var(--ink-900);
    }

    .badge.text-bg-light{
        background: var(--ink-100) !important;
        color: var(--ink-700) !important;
        border: 1px solid var(--border);
    }

    .receipt-frame{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        overflow: hidden;
        background: var(--bg);
    }
    .receipt-frame img{ display: block; width: 100%; }

    .rejection-note{
        background: #fbe6e4;
        color: var(--danger);
        border-radius: var(--radius-sm);
        padding: .75rem .9rem;
        font-size: .85rem;
    }

    /* Buttons — align every variant with the design system's radius / weight */
    .btn{ border-radius: var(--radius-sm); font-weight: 500; }
    .btn-success{ background: var(--success); border-color: var(--success); }
    .btn-success:hover{ filter: brightness(.94); }
    .btn-warning{ background: var(--warning); border-color: var(--warning); color: #fff; }
    .btn-warning:hover{ filter: brightness(.94); color: #fff; }
    .btn-outline-danger{ color: var(--danger); border-color: #f0c8c4; }
    .btn-outline-danger:hover{ background: var(--danger); border-color: var(--danger); }
    .btn-outline-dark{ color: var(--ink-700); border-color: var(--ink-300); }
    .btn-outline-dark:hover{ background: var(--ink-900); border-color: var(--ink-900); }
    .btn-outline-info{ color: var(--info); border-color: #c7dbf5; }
    .btn-outline-info:hover{ background: var(--info); border-color: var(--info); }

    /* Sidebar */
    .side-card .card-body{ padding: 1.1rem; }
    .side-card h2{ font-size: .92rem; font-weight: 700; display: flex; align-items: center; gap: .4rem; margin-bottom: .9rem; }
    .side-card h2 i{ color: var(--brand-600); }

    #public-link{ font-size: .82rem; }

    .activity-table th, .activity-table td{ font-size: .8rem; }

    .empty-state{
        text-align: center;
        padding: 1.75rem 1rem;
        color: var(--ink-500);
        font-size: .85rem;
    }

    .change-time-dialog{
        width: min(720px, calc(100vw - 2rem));
        border: 0;
        border-radius: var(--radius-lg);
        padding: 0;
        box-shadow: 0 24px 80px rgba(20, 30, 40, .24);
    }
    .change-time-dialog::backdrop{
        background: rgba(18, 24, 32, .58);
        backdrop-filter: blur(9px);
    }
    .change-time-dialog .dialog-head{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        font-weight: 800;
    }
    .change-time-dialog .dialog-body{ padding: 1.25rem; }
    .change-date-row{
        display: flex;
        gap: .6rem;
        overflow-x: auto;
        padding-bottom: .35rem;
        margin-bottom: .9rem;
    }
    .change-date-card{
        flex: 0 0 145px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--surface);
        padding: .75rem;
        text-align: right;
        color: var(--ink-700);
    }
    .change-date-card strong{ display: block; color: var(--ink-900); margin: .15rem 0; }
    .change-date-card small{ display: block; color: var(--ink-500); }
    .change-date-card.is-active{
        border-color: var(--brand-500);
        background: var(--brand-50);
        box-shadow: 0 0 0 3px rgba(47, 143, 131, .1);
    }
    .change-date-card:disabled{ opacity: .55; cursor: not-allowed; background: var(--ink-100); }
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

    @media (max-width: 991.98px){
        .info-grid{ grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575.98px){
        .info-grid{ grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            @php($payment = $reservation->payment)

            {{-- Reservation info --}}
            <div class="card card-section">
                <div class="card-header"><i class="ri-information-line"></i> اطلاعات رزرو</div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">وضعیت</div>
                            <div class="info-value"><span class="badge text-bg-light">{{ $reservation->status->label() }}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مشاور</div>
                            <div class="info-value">{{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">تاریخ</div>
                            <div class="info-value">{{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date) : '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">بازه کلی تایم</div>
                            <div class="info-value ltr">{{ $reservation->slot ? \App\Support\PersianDate::time($reservation->slot->start_time).' - '.\App\Support\PersianDate::time($reservation->slot->end_time) : '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان رزرو</div>
                            <div class="info-value ltr">{{ $reservation->slot ? \App\Support\PersianDate::time($reservation->assignedStartTime()).' - '.\App\Support\PersianDate::time($reservation->assignedEndTime()) : '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">پیش پرداخت</div>
                            <div class="info-value">{{ $reservation->prepayment_required ? 'نیاز دارد' : 'نیاز ندارد' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مبلغ</div>
                            <div class="info-value">{{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مهلت پرداخت</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->payment_deadline_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مهلت لینک</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->public_token_expires_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان تأیید</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->confirmed_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان لغو</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->cancelled_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان انقضا</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->expired_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان پایان</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->completed_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">وضعیت پرداخت</div>
                            <div class="info-value">{{ $reservation->payment?->status?->label() ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment / receipt --}}
            <div class="card card-section">
                <div class="card-header"><i class="ri-bank-card-line"></i> پرداخت و فیش پیش پرداخت</div>
                <div class="card-body">
                    @if($reservation->prepayment_required)
                        <div class="info-grid mb-3">
                            <div class="info-item">
                                <div class="info-label">مبلغ پیش پرداخت</div>
                                <div class="info-value">{{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">شماره کارت پرداخت</div>
                                <div class="info-value">
                                    @if($reservation->paymentCard)
                                        {{ $reservation->paymentCard->bank_name }} -
                                        {{ $reservation->paymentCard->holder_name }} -
                                        <span class="ltr">{{ $reservation->paymentCard->formattedNumber() }}</span>
                                    @else
                                        <span class="text-danger">کارت پرداخت انتخاب نشده است</span>
                                    @endif
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">وضعیت پرداخت</div>
                                <div class="info-value">{{ $payment?->status?->label() ?: '-' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">زمان آپلود فیش</div>
                                <div class="info-value">{{ \App\Support\PersianDate::dateTime($payment?->uploaded_at) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">زمان تأیید</div>
                                <div class="info-value">{{ \App\Support\PersianDate::dateTime($payment?->approved_at) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">زمان رد</div>
                                <div class="info-value">{{ \App\Support\PersianDate::dateTime($payment?->rejected_at) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">تأیید کننده</div>
                                <div class="info-value">{{ $payment?->approver?->name ?: '-' }}</div>
                            </div>
                        </div>

                        @if($payment?->rejection_reason)
                            <div class="rejection-note mb-3">
                                <strong>دلیل رد:</strong> {{ $payment->rejection_reason }}
                            </div>
                        @endif

                        @if($payment?->receipt_image_path)
                            <div class="receipt-frame mb-3">
                                <img src="{{ route('admin.reservations.receipt', $reservation) }}" alt="فیش پیش پرداخت">
                            </div>

                            <div class="row g-2">
                                @can('approve_payments')
                                    <div class="col-md-6">
                                        <form method="post" action="{{ route('admin.payments.approve', $payment) }}">
                                            @csrf
                                            <button class="btn btn-success w-100">
                                                <i class="ri-checkbox-circle-line align-middle"></i> تأیید فیش
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                                @can('reject_payments')
                                    <div class="col-md-6">
                                        <form method="post" action="{{ route('admin.payments.reject', $payment) }}">
                                            @csrf
                                            <textarea name="rejection_reason" rows="2" class="form-control mb-2" placeholder="دلیل رد فیش" required>{{ old('rejection_reason') }}</textarea>
                                            <button class="btn btn-outline-danger w-100">
                                                <i class="ri-close-circle-line align-middle"></i> رد فیش
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="ri-image-line d-block mb-1" style="font-size:1.6rem;color:var(--ink-300)"></i>
                                فیشی توسط دانش آموز آپلود نشده است
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="ri-checkbox-circle-line d-block mb-1" style="font-size:1.6rem;color:var(--ink-300)"></i>
                            برای این رزرو پیش پرداخت لازم نیست
                        </div>
                    @endif
                </div>
            </div>

            {{-- Report cards --}}
            <div class="card card-section">
                <div class="card-header"><i class="ri-file-upload-line"></i> کارنامه دانش‌آموز</div>
                <div class="card-body">
                    @forelse($reservation->reportCards as $document)
                        <div class="info-grid mb-3">
                            <div class="info-item">
                                <div class="info-label">فایل کارنامه</div>
                                <div class="info-value">{{ $document->original_name }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">منبع ثبت</div>
                                <div class="info-value">{{ $document->sourceLabel() }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">زمان ثبت</div>
                                <div class="info-value">{{ \App\Support\PersianDate::dateTime($document->created_at) }}</div>
                            </div>
                        </div>
                        <a class="btn btn-outline-primary mb-3" href="{{ route('admin.reservations.documents.show', [$reservation, $document]) }}">
                            <i class="ri-download-line align-middle"></i> مشاهده کارنامه
                        </a>
                    @empty
                        <div class="empty-state">
                            <i class="ri-file-line d-block mb-1" style="font-size:1.6rem;color:var(--ink-300)"></i>
                            کارنامه‌ای برای این رزرو ثبت نشده است.
                        </div>
                    @endforelse

                    @can('update_reservations')
                        <form method="post" action="{{ route('admin.reservations.documents.report-card.store', $reservation) }}" enctype="multipart/form-data" class="mt-3">
                            @csrf
                            <label class="form-label">{{ $reservation->reportCards->isEmpty() ? 'آپلود کارنامه' : 'جایگزینی کارنامه' }}</label>
                            <input type="file" name="report_card" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                            <button class="btn btn-primary">
                                <i class="ri-upload-cloud-2-line align-middle"></i> ثبت کارنامه
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Student --}}
            <div class="card card-section">
                <div class="card-header"><i class="ri-graduation-cap-line"></i> دانش آموز</div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">نام و نام خانوادگی</div>
                            <div class="info-value">{{ $reservation->student?->full_name ?: '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">رشته</div>
                            <div class="info-value">{{ $reservation->student?->major ?: '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">تراز</div>
                            <div class="info-value">{{ $reservation->student?->score ?: '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">نوع کنکور</div>
                            <div class="info-value">{{ $reservation->student?->examTypeLabel() ?: '-' }}</div>
                        </div>
                        <div class="info-item" style="grid-column: span 2;">
                            <div class="info-label">شمارهها</div>
                            <div class="info-value ltr">{{ $reservation->student?->phones->pluck('phone')->implode(' / ') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Activity log --}}
            <div class="card card-section mb-0">
                <div class="card-header"><i class="ri-history-line"></i> تاریخچه فعالیت</div>
                <div class="table-responsive">
                    <table class="table table-hover activity-table mb-0">
                        <thead><tr><th>زمان</th><th>کاربر</th><th>عملیات</th><th>توضیح</th></tr></thead>
                        <tbody>
                        @forelse($reservation->activityLogs->sortByDesc('created_at') as $log)
                            <tr>
                                <td class="text-nowrap">{{ \App\Support\PersianDate::dateTime($log->created_at) }}</td>
                                <td>{{ $log->user?->name ?: 'سیستم' }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state">فعالیتی ثبت نشده است</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Public link --}}
            <div class="card side-card card-section">
                <div class="card-body">
                    <h2><i class="ri-links-line"></i> لینک دانش آموز</h2>
                    <input class="form-control ltr mb-2" id="public-link" value="{{ $publicUrl }}" readonly>
                    <button class="btn btn-outline-primary w-100" type="button" onclick="navigator.clipboard.writeText(document.getElementById('public-link').value)">
                        <i class="ri-file-copy-line align-middle"></i> کپی لینک
                    </button>
                    @can('update_reservations')
                        <form method="post" action="{{ route('admin.reservations.regenerate-link', $reservation) }}" class="mt-2">
                            @csrf
                            <button class="btn btn-outline-secondary w-100">
                                <i class="ri-refresh-line align-middle"></i> ساخت لینک جدید
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Change slot --}}
            @can('change_reservation_slot')
                <div class="card side-card card-section">
                    <div class="card-body">
                        <h2><i class="ri-calendar-2-line"></i> تغییر تایم</h2>
                        <button class="btn btn-warning w-100" type="button" onclick="document.getElementById('change-time-dialog').showModal()">
                            <i class="ri-exchange-line align-middle"></i> تغییر تایم
                        </button>
                        <dialog class="change-time-dialog" id="change-time-dialog">
                            <div class="dialog-head">
                                <span><i class="ri-calendar-check-line align-middle"></i> انتخاب نوبت</span>
                                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="this.closest('dialog').close()">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>
                            <form method="post" action="{{ route('admin.reservations.change-slot', $reservation) }}" class="dialog-body">
                                @csrf
                                <div class="alert alert-info mb-3">
                                    زمان فعلی:
                                    {{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date) : '-' }}
                                    -
                                    {{ \App\Support\PersianDate::time($reservation->assignedStartTime()) }}
                                    تا
                                    {{ \App\Support\PersianDate::time($reservation->assignedEndTime()) }}
                                </div>
                                <label class="form-label">تاریخ جدید</label>
                                <div class="change-date-row">
                                    @foreach($slotDateGroups ?? [] as $dateGroup)
                                        <button
                                            type="button"
                                            class="change-date-card"
                                            data-change-date-card
                                            data-date-key="{{ $dateGroup['date'] }}"
                                            @disabled(($dateGroup['available_count'] ?? 0) === 0 && ! collect($dateGroup['intervals'])->contains('slot_id', (int) $reservation->slot_id))
                                        >
                                            <span>{{ $dateGroup['weekday_label'] }}</span>
                                            <strong>{{ $dateGroup['jalali_date'] }}</strong>
                                            <small>{{ $dateGroup['advisor_label'] ?: '-' }}</small>
                                            <small>{{ \App\Support\PersianDate::number($dateGroup['available_count']) }} نوبت آزاد</small>
                                        </button>
                                    @endforeach
                                </div>
                                <select name="slot_id" id="change_slot_id" class="form-select mb-3 d-none" required>
                                    <option value="">انتخاب کنید</option>
                                    @foreach($availableSlots as $slot)
                                        @php($hasAvailableInterval = collect($slotIntervals[$slot->id] ?? [])->contains('available', true))
                                        <option value="{{ $slot->id }}" @selected($reservation->slot_id == $slot->id) @disabled(! $hasAvailableInterval && $reservation->slot_id != $slot->id)>
                                            {{ \App\Support\PersianDate::date($slot->date) }}
                                            - {{ \App\Support\PersianDate::time($slot->start_time) }} تا {{ \App\Support\PersianDate::time($slot->end_time) }}
                                            - {{ $slot->advisor?->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label class="form-label">زمان رزرو</label>
                                <select name="reservation_interval" id="change_reservation_interval" class="form-select mb-3" required data-selected="{{ substr((string) $reservation->assignedStartTime(), 0, 5).'|'.substr((string) $reservation->assignedEndTime(), 0, 5) }}"></select>
                                <button class="btn btn-warning w-100">
                                    <i class="ri-checkbox-circle-line align-middle"></i> ثبت تغییر زمان
                                </button>
                            </form>
                        </dialog>
                    </div>
                </div>
            @endcan

            {{-- Follow-up --}}
            <div class="card side-card card-section">
                <div class="card-body">
                    <h2><i class="ri-calendar-event-line"></i> تایم مراجعه بعدی</h2>

                    @if($activeFollowUp)
                        <div class="info-item mb-2">
                            <div class="info-label">تاریخ</div>
                            <div class="info-value">{{ \App\Support\PersianDate::date($activeFollowUp->follow_up_date) }}</div>
                        </div>
                        <div class="info-item mb-2">
                            <div class="info-label">زمان</div>
                            <div class="info-value ltr">{{ \App\Support\PersianDate::time($activeFollowUp->reserved_start_time).' - '.\App\Support\PersianDate::time($activeFollowUp->reserved_end_time) }}</div>
                        </div>
                        <div class="info-item mb-3">
                            <div class="info-label">مشاور</div>
                            <div class="info-value">{{ $activeFollowUp->advisor?->name ?: $activeFollowUp->slot?->advisor?->name }}</div>
                        </div>
                    @else
                        <div class="empty-state py-3">
                            هنوز تایم مراجعه بعدی ثبت نشده است.
                        </div>
                    @endif

                    @can('update_reservations')
                        <button class="btn btn-outline-primary w-100 mb-2" type="button" onclick="document.getElementById('follow-up-dialog').showModal()">
                            <i class="ri-calendar-check-line align-middle"></i>
                            {{ $activeFollowUp ? 'ویرایش تایم مراجعه بعدی' : 'ثبت تایم مراجعه بعدی' }}
                        </button>

                        @if($activeFollowUp)
                            <form method="post" action="{{ route('admin.reservations.follow-up.destroy', [$reservation, $activeFollowUp]) }}">
                                @csrf
                                @method('delete')
                                <button class="btn btn-outline-danger w-100">
                                    <i class="ri-delete-bin-line align-middle"></i> حذف تایم مراجعه بعدی
                                </button>
                            </form>
                        @endif
                    @endcan

                    <dialog class="change-time-dialog" id="follow-up-dialog">
                        <div class="dialog-head">
                            <span><i class="ri-calendar-event-line align-middle"></i> تایم مراجعه بعدی</span>
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="this.closest('dialog').close()">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <form method="post" action="{{ route('admin.reservations.follow-up.store', $reservation) }}" class="dialog-body">
                            @csrf

                            @if(($followUpSlotDateGroups ?? collect())->isNotEmpty())
                                <label class="form-label">تاریخ مراجعه</label>
                                <div class="change-date-row">
                                    @foreach($followUpSlotDateGroups as $dateGroup)
                                        <button
                                            type="button"
                                            class="change-date-card"
                                            data-follow-up-date-card
                                            data-date-key="{{ $dateGroup['date'] }}"
                                            @disabled(($dateGroup['available_count'] ?? 0) === 0 && ! collect($dateGroup['intervals'])->contains('slot_id', (int) $activeFollowUp?->slot_id))
                                        >
                                            <span>{{ $dateGroup['weekday_label'] }}</span>
                                            <strong>{{ $dateGroup['jalali_date'] }}</strong>
                                            <small>{{ $dateGroup['advisor_label'] ?: '-' }}</small>
                                            <small>{{ \App\Support\PersianDate::number($dateGroup['available_count']) }} نوبت آزاد</small>
                                        </button>
                                    @endforeach
                                </div>

                                <input type="hidden" name="slot_id" id="follow_up_slot_id" value="{{ old('slot_id', $activeFollowUp?->slot_id) }}">
                                <input type="hidden" name="reservation_interval" id="follow_up_reservation_interval" value="{{ old('reservation_interval', $activeFollowUp ? substr((string) $activeFollowUp->reserved_start_time, 0, 5).'|'.substr((string) $activeFollowUp->reserved_end_time, 0, 5) : '') }}" data-selected="{{ $activeFollowUp ? substr((string) $activeFollowUp->reserved_start_time, 0, 5).'|'.substr((string) $activeFollowUp->reserved_end_time, 0, 5) : '' }}">

                                <label class="form-label">زمان مراجعه</label>
                                <div class="time-chip-list mb-3" data-follow-up-time-chips></div>

                                <div id="follow-up-interval-summary" class="alert alert-info mb-3">
                                    <div><strong>تاریخ:</strong> <span data-follow-up-date>-</span></div>
                                    <div><strong>بازه کلی تایم:</strong> <span data-follow-up-slot-range>-</span></div>
                                    <div><strong>زمان مراجعه بعدی:</strong> <span data-follow-up-range>-</span></div>
                                </div>

                                <label class="form-label">یادداشت</label>
                                <textarea name="note" rows="2" class="form-control mb-3">{{ old('note', $activeFollowUp?->note) }}</textarea>

                                <button class="btn btn-primary w-100">
                                    <i class="ri-save-line align-middle"></i> ذخیره تایم مراجعه بعدی
                                </button>
                            @else
                                <div class="empty-state">برای این تاریخ نوبتی تعریف نشده است.</div>
                            @endif
                        </form>
                    </dialog>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card side-card card-section mb-0">
                <div class="card-body">
                    <h2><i class="ri-tools-line"></i> عملیات</h2>
                    @can('update_reservations')
                        <a class="btn btn-outline-primary w-100 mb-2" href="{{ route('admin.reservations.edit', $reservation) }}">
                            <i class="ri-edit-line align-middle"></i> ویرایش
                        </a>
                    @endcan
                    @can('confirm_reservations')
                        <form method="post" action="{{ route('admin.reservations.complete', $reservation) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success w-100">
                                <i class="ri-checkbox-circle-line align-middle"></i> انجام شده
                            </button>
                        </form>
                        <form method="post" action="{{ route('admin.reservations.no-show', $reservation) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-outline-dark w-100">
                                <i class="ri-user-unfollow-line align-middle"></i> عدم حضور
                            </button>
                        </form>
                    @endcan
                    @can('cancel_reservations')
                        <form method="post" action="{{ route('admin.reservations.cancel', $reservation) }}">
                            @csrf
                            <textarea name="reason" class="form-control mb-2" rows="2" placeholder="دلیل لغو"></textarea>
                            <button class="btn btn-outline-danger w-100">
                                <i class="ri-close-circle-line align-middle"></i> لغو رزرو
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

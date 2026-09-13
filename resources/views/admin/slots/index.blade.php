@extends('layouts.admin')

@section('title', 'تایم‌ها')

@section('actions')
    @can('create_slots')
        <a class="btn btn-primary" href="{{ route('admin.slots.create') }}">
            <i class="ri-add-line align-middle"></i> ایجاد تایم
        </a>
    @endcan
@endsection

@push('styles')
<style>
    .filter-card .card-body{ padding: 1.1rem 1.25rem; }
    .filter-card .form-label{ font-size: .78rem; font-weight: 600; color: var(--ink-500); margin-bottom: .35rem; }
    .filter-card .btn{ height: 40px; }
    .schedule-card .card-header{ display:flex; align-items:center; gap:.5rem; font-weight:800; }
    .schedule-card .card-header i{ color:var(--brand-600); }
    .appointment-picker{ border:1px solid var(--border); border-radius:var(--radius-md); padding:1rem; background:var(--surface); }
    .appointment-advisor{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.85rem; color:var(--ink-700); font-size:.85rem; }
    .date-card-row{ display:flex; gap:.6rem; overflow-x:auto; padding-bottom:.35rem; margin-bottom:.9rem; }
    .date-card{ flex:0 0 155px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--surface); padding:.75rem; text-align:right; color:var(--ink-700); }
    .date-card strong{ display:block; color:var(--ink-900); margin:.15rem 0; }
    .date-card small{ display:block; color:var(--ink-500); }
    .date-card.is-active{ border-color:var(--brand-500); background:var(--brand-50); box-shadow:0 0 0 3px rgba(47,143,131,.1); }
    .time-chip-list{ display:flex; flex-wrap:wrap; gap:.75rem; min-height:46px; }
    .time-chip-item{ display:inline-flex; align-items:center; gap:.3rem; flex-wrap:nowrap; }
    .time-chip{ border:1px solid var(--border); border-radius:999px; background:var(--surface); color:var(--ink-700); padding:.48rem .85rem; font-size:.83rem; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; }
    .time-chip:hover{ border-color:var(--brand-500); color:var(--brand-700); }
    .time-chip.is-free{ background:#e2f5ec; color:var(--success); border-color:#bfe8d3; }
    .time-chip.is-occupied{ background:#fbf1de; color:var(--warning); border-color:#efd7ab; }
    .time-chip.is-follow-up{ background:#e5eefb; color:var(--info); border-color:#c7dbf5; }
    .time-chip.is-disabled{ opacity:.55; cursor:not-allowed; text-decoration:line-through; }
    .chip-meta{ font-size:.72rem; opacity:.85; }
    .slot-action-group{ display:inline-flex; align-items:center; gap:.2rem; }
    .slot-delete-form{ display:inline-flex; margin:0; }
    .slot-action-button{ width:31px; height:31px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; }
    @media (max-width:575.98px){ .time-chip-item{ width:100%; justify-content:space-between; } .time-chip{ flex:1; justify-content:center; } }
    .appointment-empty{ color:var(--warning); font-size:.86rem; padding:.75rem 0 0; }
    .empty-state{ text-align:center; padding:2.5rem 1rem; color:var(--ink-500); }
    .empty-state i{ font-size:2.2rem; color:var(--ink-300); margin-bottom:.5rem; display:block; }
    @media (max-width:575.98px){ .date-card{ flex-basis:132px; } .appointment-advisor{ align-items:flex-start; flex-direction:column; } }
    .day-delete-summary{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
    .day-delete-summary div{ padding:.65rem; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--bg); font-size:.82rem; }
    @media (max-width:575.98px){ .day-delete-summary{ grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cards = Array.from(document.querySelectorAll('[data-schedule-date-card]'));
        const groups = Array.from(document.querySelectorAll('[data-schedule-intervals]'));

        function showDate(dateKey) {
            cards.forEach((card) => card.classList.toggle('is-active', card.dataset.dateKey === dateKey));
            groups.forEach((group) => group.hidden = group.dataset.dateKey !== dateKey);
        }

        cards.forEach((card) => card.addEventListener('click', () => showDate(card.dataset.dateKey)));
        showDate(cards.find((card) => card.dataset.defaultSelected === '1')?.dataset.dateKey || cards[0]?.dataset.dateKey);

        const dayDeleteForm = document.getElementById('day-delete-form');
        const dayDeleteModal = document.getElementById('day-delete-modal');
        dayDeleteForm?.addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = new FormData(dayDeleteForm);
            try {
                const response = await fetch(dayDeleteForm.dataset.previewUrl, {method: 'POST', body, headers: {'X-CSRF-TOKEN': body.get('_token'), 'Accept': 'application/json'}});
                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    alert(payload.message || 'امکان بررسی تایم‌های این روز وجود ندارد.');

                    return;
                }

                const summary = await response.json();
                document.querySelectorAll('[data-day-delete-value]').forEach((node) => node.textContent = new Intl.NumberFormat('fa-IR').format(summary[node.dataset.dayDeleteValue] || 0));
                document.getElementById('day-delete-date').textContent = dayDeleteForm.querySelector('[name="date"]').value;
                dayDeleteModal.querySelectorAll('[name="date"]').forEach((input) => input.value = dayDeleteForm.querySelector('[name="date"]').value);
                dayDeleteModal.querySelectorAll('[name="advisor_id"]').forEach((input) => input.value = dayDeleteForm.querySelector('[name="advisor_id"]').value);
                bootstrap.Modal.getOrCreateInstance(dayDeleteModal).show();
            } catch (error) {
                alert('ارتباط با سرور برقرار نشد. دوباره تلاش کنید.');
            }
        });
    });
</script>
@endpush

@section('content')
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-calendar-line align-middle"></i> تاریخ</label>
                    <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(request('date')) }}" class="form-control jalali-date-picker" autocomplete="off" placeholder="انتخاب تاریخ">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-user-line align-middle"></i> مشاور</label>
                    <select name="advisor_id" class="form-select">
                        <option value="">همه</option>
                        @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}" @selected(request('advisor_id') == $advisor->id)>{{ $advisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label"><i class="ri-flag-line align-middle"></i> وضعیت تایم</label>
                    <select name="status" class="form-select">
                        <option value="">همه</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label"><i class="ri-checkbox-circle-line align-middle"></i> نوع نوبت</label>
                    <select name="availability" class="form-select">
                        <option value="">همه</option>
                        @foreach($availabilityOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('availability') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="ri-filter-3-line align-middle"></i> فیلتر</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}" title="پاکسازی">
                        <i class="ri-refresh-line align-middle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @can('delete_slots')
        <div class="card filter-card mb-3">
            <div class="card-body">
                <form id="day-delete-form" method="post" action="{{ route('admin.slots.day-deletion-preview') }}" class="row g-3 align-items-end" data-preview-url="{{ route('admin.slots.day-deletion-preview') }}">
                    @csrf
                    <div class="col-md-4"><label class="form-label">حذف تایم‌های یک روز</label><input type="text" name="date" class="form-control jalali-date-picker" autocomplete="off" placeholder="انتخاب تاریخ" required></div>
                    <div class="col-md-4"><label class="form-label">مشاور (اختیاری)</label><select name="advisor_id" class="form-select"><option value="">همه مشاوران</option>@foreach($advisors as $advisor)<option value="{{ $advisor->id }}">{{ $advisor->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><button class="btn btn-outline-danger w-100"><i class="ri-delete-bin-line"></i> بررسی حذف تایم‌های روز</button></div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="day-delete-modal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">حذف تایم‌های یک روز</h5><button type="button" class="btn-close m-0" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>تاریخ انتخاب‌شده: <strong id="day-delete-date"></strong></p><div class="day-delete-summary"><div>تعداد کل تایم‌ها: <strong data-day-delete-value="total"></strong></div><div>تایم‌های آزاد: <strong data-day-delete-value="free"></strong></div><div>تایم‌های دارای رزرو: <strong data-day-delete-value="with_reservations"></strong></div><div>تایم‌های قابل حذف: <strong data-day-delete-value="deletable"></strong></div><div>تایم‌های غیرقابل حذف: <strong data-day-delete-value="not_deletable"></strong></div></div><p class="text-warning small mt-3 mb-0">برخی تایم‌های این روز دارای رزرو هستند و حذف نمی‌شوند.</p></div><div class="modal-footer"><form method="post" action="{{ route('admin.slots.bulk-delete-day') }}" onsubmit="return confirm('آیا از حذف تایم‌های آزاد این روز مطمئن هستید؟')">@csrf<input type="hidden" name="date"><input type="hidden" name="advisor_id"><input type="hidden" name="action" value="delete"><button class="btn btn-danger">حذف تایم‌های آزاد</button></form><form method="post" action="{{ route('admin.slots.bulk-delete-day') }}">@csrf<input type="hidden" name="date"><input type="hidden" name="advisor_id"><input type="hidden" name="action" value="deactivate"><button class="btn btn-outline-secondary">غیرفعال کردن تایم‌های روز</button></form></div></div></div></div>
    @endcan

    <div class="card schedule-card">
        <div class="card-header"><i class="ri-calendar-check-line"></i> زمان‌بندی رزرو</div>
        <div class="card-body">
            @if($slotDateGroups->isNotEmpty())
                <div class="appointment-picker">
                    <div class="appointment-advisor">
                        <div><i class="ri-calendar-2-line align-middle"></i> نوبت‌های این روز</div>
                        <span>{{ \App\Support\PersianDate::number($slotDateGroups->sum('available_count')) }} نوبت آزاد</span>
                    </div>

                    <div class="date-card-row">
                        @foreach($slotDateGroups as $dateGroup)
                            <button
                                type="button"
                                class="date-card"
                                data-schedule-date-card
                                data-date-key="{{ $dateGroup['date'] }}"
                                data-default-selected="{{ request('date') && request('date') === $dateGroup['jalali_date'] ? '1' : '0' }}"
                            >
                                <span>{{ $dateGroup['weekday_label'] }}</span>
                                <strong>{{ $dateGroup['jalali_date'] }}</strong>
                                <small>{{ $dateGroup['advisor_label'] ?: 'همه مشاوران' }}</small>
                                <small>{{ \App\Support\PersianDate::number($dateGroup['available_count']) }} نوبت آزاد</small>
                                <small>{{ \App\Support\PersianDate::number($dateGroup['reserved_count']) }} نوبت رزرو شده</small>
                            </button>
                        @endforeach
                    </div>

                    @foreach($slotDateGroups as $dateGroup)
                        <div class="time-chip-list" data-schedule-intervals data-date-key="{{ $dateGroup['date'] }}" hidden>
                            @forelse($dateGroup['intervals'] as $interval)
                                @php
                                    $chipClass = $interval['available']
                                        ? 'is-free'
                                        : ($interval['status'] === 'follow_up' ? 'is-follow-up' : 'is-occupied');
                                    $slotHasActiveReservations = ($interval['slot_active_reservations_count'] ?? 0) > 0;
                                @endphp

                                <div class="time-chip-item">
                                    @if($interval['available'])
                                        @can('create_reservations')
                                            <a
                                                class="time-chip {{ $chipClass }}"
                                                href="{{ route('admin.reservations.create', ['slot_id' => $interval['slot_id'], 'reservation_interval' => $interval['value']]) }}"
                                                title="ایجاد رزرو برای این تایم"
                                            >
                                                <span class="ltr">{{ $interval['label'] }}</span>
                                                <span class="chip-meta">{{ $interval['status_label'] }}</span>
                                            </a>
                                        @else
                                            <span class="time-chip {{ $chipClass }} is-disabled">
                                                <span class="ltr">{{ $interval['label'] }}</span>
                                                <span class="chip-meta">{{ $interval['status_label'] }}</span>
                                            </span>
                                        @endcan
                                    @elseif($interval['reservation_id'])
                                        @can('view_reservations')
                                            <a
                                                class="time-chip {{ $chipClass }}"
                                                href="{{ route('admin.reservations.show', $interval['reservation_id']) }}"
                                                title="مشاهده رزرو"
                                            >
                                                <span class="ltr">{{ $interval['label'] }}</span>
                                                <span class="chip-meta">{{ $interval['status_label'] }}</span>
                                                @if($interval['student_name'])
                                                    <span class="chip-meta">{{ $interval['student_name'] }}</span>
                                                @endif
                                            </a>
                                        @else
                                            <span class="time-chip {{ $chipClass }} is-disabled">
                                                <span class="ltr">{{ $interval['label'] }}</span>
                                                <span class="chip-meta">{{ $interval['status_label'] }}</span>
                                            </span>
                                        @endcan
                                    @else
                                        <span class="time-chip is-disabled">
                                            <span class="ltr">{{ $interval['label'] }}</span>
                                            <span class="chip-meta">{{ $interval['status_label'] }}</span>
                                        </span>
                                    @endif

                                    @include('admin.slots._slot-actions', [
                                        'slotId' => $interval['slot_id'],
                                        'canDelete' => ! $slotHasActiveReservations,
                                    ])
                                </div>
                            @empty
                                <div class="appointment-empty">برای این تاریخ نوبتی تعریف نشده است.</div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="ri-calendar-close-line"></i>
                    برای این تاریخ نوبتی تعریف نشده است.
                </div>
            @endif
        </div>
    </div>
@endsection

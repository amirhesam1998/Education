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
    .time-chip-list{ display:flex; flex-wrap:wrap; gap:.55rem; min-height:46px; }
    .time-chip{ border:1px solid var(--border); border-radius:999px; background:var(--surface); color:var(--ink-700); padding:.48rem .85rem; font-size:.83rem; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; }
    .time-chip:hover{ border-color:var(--brand-500); color:var(--brand-700); }
    .time-chip.is-free{ background:#e2f5ec; color:var(--success); border-color:#bfe8d3; }
    .time-chip.is-occupied{ background:#fbf1de; color:var(--warning); border-color:#efd7ab; }
    .time-chip.is-follow-up{ background:#e5eefb; color:var(--info); border-color:#c7dbf5; }
    .time-chip.is-disabled{ opacity:.55; cursor:not-allowed; text-decoration:line-through; }
    .chip-meta{ font-size:.72rem; opacity:.85; }
    .appointment-empty{ color:var(--warning); font-size:.86rem; padding:.75rem 0 0; }
    .empty-state{ text-align:center; padding:2.5rem 1rem; color:var(--ink-500); }
    .empty-state i{ font-size:2.2rem; color:var(--ink-300); margin-bottom:.5rem; display:block; }
    @media (max-width:575.98px){ .date-card{ flex-basis:132px; } .appointment-advisor{ align-items:flex-start; flex-direction:column; } }
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
                                @endphp

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

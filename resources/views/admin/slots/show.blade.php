@extends('layouts.admin')

@section('title', 'جزئیات تایم')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}">
        <i class="ri-arrow-right-line align-middle"></i> بازگشت
    </a>
@endsection

@push('styles')
<style>
    /* ===========================================================
       Slot detail — page-local styles
    =========================================================== */
    .info-grid{
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .9rem;
    }
    .info-item{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .9rem 1rem;
        background: var(--bg);
        display: flex;
        align-items: center;
        gap: .7rem;
    }
    .info-icon{
        width: 38px; height: 38px;
        border-radius: 10px;
        background: var(--brand-100);
        color: var(--brand-700);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .info-label{ font-size: .74rem; color: var(--ink-500); }
    .info-value{ font-size: .92rem; font-weight: 700; color: var(--ink-900); }

    .filter-card .form-label{
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-500);
        margin-bottom: .35rem;
    }

    .card-section-title{
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .card-section-title i{ color: var(--brand-600); }

    /* Highlighted timeline row = "this" slot within the day */
    tr.timeline-selected > td{
        background: var(--brand-50) !important;
        position: relative;
    }
    tr.timeline-selected > td:first-child::before{
        content: "";
        position: absolute;
        inset-inline-start: 0;
        top: 0; bottom: 0;
        width: 3px;
        background: var(--brand-500);
    }

    .badge.text-bg-secondary{ background: var(--ink-100) !important; color: var(--ink-700) !important; }
    .badge.text-bg-success  { background: #e2f5ec !important; color: var(--success) !important; }
    .badge.text-bg-warning  { background: #fbf1de !important; color: var(--warning) !important; }
    .badge.text-bg-danger   { background: #fbe6e4 !important; color: var(--danger) !important; }
    .badge.text-bg-info     { background: #e5eefb !important; color: var(--info) !important; }

    .empty-state{
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }
    .empty-state i{ font-size: 2.2rem; color: var(--ink-300); margin-bottom: .5rem; display: block; }

    @media (max-width: 991.98px){
        .info-grid{ grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575.98px){
        .info-grid{ grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

    {{-- Slot summary --}}
    <div class="card mb-3">
        <div class="card-header">
            <span class="card-section-title"><i class="ri-information-line"></i> اطلاعات تایم</span>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon"><i class="ri-user-line"></i></div>
                    <div>
                        <div class="info-label">مشاور</div>
                        <div class="info-value">{{ $slot->advisor?->name ?: '-' }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ri-calendar-line"></i></div>
                    <div>
                        <div class="info-label">تاریخ</div>
                        <div class="info-value">{{ \App\Support\PersianDate::date($slot->date) }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ri-flag-line"></i></div>
                    <div>
                        <div class="info-label">وضعیت فعلی</div>
                        <div class="info-value">{{ $slot->status->label() }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ri-time-line"></i></div>
                    <div>
                        <div class="info-label">زمان شروع</div>
                        <div class="info-value ltr">{{ \App\Support\PersianDate::time($slot->start_time) }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ri-time-line"></i></div>
                    <div>
                        <div class="info-label">زمان پایان</div>
                        <div class="info-value ltr">{{ \App\Support\PersianDate::time($slot->end_time) }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ri-group-line"></i></div>
                    <div>
                        <div class="info-label">ظرفیت</div>
                        <div class="info-value">{{ \App\Support\PersianDate::number($slot->capacity) }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ri-checkbox-circle-line"></i></div>
                    <div>
                        <div class="info-label">رزرو فعال</div>
                        <div class="info-value">{{ \App\Support\PersianDate::number($activeReservationsCount) }}</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ri-hourglass-line"></i></div>
                    <div>
                        <div class="info-label">ظرفیت باقی‌مانده</div>
                        <div class="info-value">{{ \App\Support\PersianDate::number($remainingCapacity) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-user-line align-middle"></i> مشاور</label>
                    <select name="advisor_id" class="form-select">
                        <option value="">همه</option>
                        @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}" @selected(request('advisor_id') == $advisor->id)>{{ $advisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-flag-line align-middle"></i> وضعیت</label>
                    <select name="status" class="form-select">
                        <option value="">همه</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-eye-line align-middle"></i> نمایش</label>
                    <select name="availability" class="form-select">
                        <option value="">همه تایم‌ها</option>
                        <option value="available" @selected(request('availability') === 'available')>فقط آزاد</option>
                        <option value="reserved" @selected(request('availability') === 'reserved')>فقط رزرو شده</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1">
                        <i class="ri-filter-3-line align-middle"></i> فیلتر
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.show', $slot) }}" title="پاکسازی">
                        <i class="ri-refresh-line align-middle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Day timeline --}}
    <div class="card">
        <div class="card-header">
            <span class="card-section-title"><i class="ri-calendar-2-line"></i> جدول زمانی روز</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>بازه زمانی</th>
                    <th>مشاور</th>
                    <th>وضعیت تایم</th>
                    <th>وضعیت رزرو</th>
                    <th>دانش‌آموز</th>
                    <th>شماره تماس</th>
                    <th>وضعیت پرداخت</th>
                    <th>ظرفیت باقی‌مانده</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($timelineRows as $row)
                    <tr @class(['timeline-selected' => $row['is_selected']])>
                        <td class="text-nowrap ltr">
                            {{ \App\Support\PersianDate::time($row['time_start']) }}
                            —
                            {{ \App\Support\PersianDate::time($row['time_end']) }}
                        </td>
                        <td>{{ $row['advisor_name'] }}</td>
                        <td><span class="badge {{ $row['slot_status']['class'] }}">{{ $row['slot_status']['label'] }}</span></td>
                        <td>
                            @if($row['reservation_status'])
                                <span class="badge {{ $row['reservation_status']['class'] }}">{{ $row['reservation_status']['label'] }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $row['student_name'] }}</td>
                        <td class="ltr">{{ $row['student_phone'] }}</td>
                        <td>{{ $row['payment_status'] }}</td>
                        <td>{{ \App\Support\PersianDate::number($row['remaining_capacity']) }}</td>
                        <td>
                            @if($row['reservation'])
                                @can('view_reservations')
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reservations.show', $row['reservation']) }}">
                                        <i class="ri-eye-line align-middle"></i> مشاهده رزرو
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endcan
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="ri-calendar-close-line"></i>
                                تایمی برای این روز یافت نشد
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection


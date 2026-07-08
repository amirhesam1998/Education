@extends('layouts.admin')

@section('title', 'جزئیات تایم')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}">بازگشت</a>
@endsection

@section('content')
    <style>
        .timeline-selected > * { background: #eef2ff !important; }
    </style>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5 mb-3">اطلاعات تایم</h2>
            <div class="row g-3">
                <div class="col-md-4"><strong>مشاور:</strong> {{ $slot->advisor?->name ?: '-' }}</div>
                <div class="col-md-4"><strong>تاریخ:</strong> {{ \App\Support\PersianDate::date($slot->date) }}</div>
                <div class="col-md-4"><strong>وضعیت فعلی:</strong> {{ $slot->status->label() }}</div>
                <div class="col-md-4"><strong>زمان شروع:</strong> {{ \App\Support\PersianDate::time($slot->start_time) }}</div>
                <div class="col-md-4"><strong>زمان پایان:</strong> {{ \App\Support\PersianDate::time($slot->end_time) }}</div>
                <div class="col-md-4"><strong>ظرفیت:</strong> {{ \App\Support\PersianDate::number($slot->capacity) }}</div>
                <div class="col-md-4"><strong>رزرو فعال:</strong> {{ \App\Support\PersianDate::number($activeReservationsCount) }}</div>
                <div class="col-md-4"><strong>ظرفیت باقی‌مانده:</strong> {{ \App\Support\PersianDate::number($remainingCapacity) }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="get">
                <div class="col-md-3">
                    <label class="form-label">مشاور</label>
                    <select name="advisor_id" class="form-select">
                        <option value="">همه</option>
                        @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}" @selected(request('advisor_id') == $advisor->id)>{{ $advisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">وضعیت</label>
                    <select name="status" class="form-select">
                        <option value="">همه</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">نمایش</label>
                    <select name="availability" class="form-select">
                        <option value="">همه تایم‌ها</option>
                        <option value="available" @selected(request('availability') === 'available')>فقط آزاد</option>
                        <option value="reserved" @selected(request('availability') === 'reserved')>فقط رزرو شده</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-outline-primary">فیلتر</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.show', $slot) }}">پاکسازی</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <h2 class="h5 mb-0">جدول زمانی روز</h2>
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
                        <td class="text-nowrap">
                            {{ \App\Support\PersianDate::time($row['time_start']) }}
                            تا
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
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reservations.show', $row['reservation']) }}">مشاهده رزرو</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endcan
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">تایمی برای این روز یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

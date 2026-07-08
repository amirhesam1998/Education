@extends('layouts.admin')

@section('title', 'رزروها')

@section('actions')
    @can('create_reservations')
        <a class="btn btn-primary" href="{{ route('admin.reservations.create') }}">رزرو جدید</a>
    @endcan
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="get">
                <div class="col-md-2">
                    <label class="form-label">وضعیت</label>
                    <select name="status" class="form-select">
                        <option value="">همه</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">تاریخ</label>
                    <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(request('date')) }}" class="form-control jalali-date-picker" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label">مشاور</label>
                    <select name="advisor_id" class="form-select">
                        <option value="">همه</option>
                        @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}" @selected(request('advisor_id') == $advisor->id)>{{ $advisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">نام دانش آموز</label>
                    <input name="student_name" value="{{ request('student_name') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">شماره تماس</label>
                    <input name="phone" value="{{ request('phone') }}" class="form-control ltr">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-outline-primary">فیلتر</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.index') }}">پاکسازی</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>دانش آموز</th>
                    <th>شماره تماس</th>
                    <th>تایم</th>
                    <th>مشاور</th>
                    <th>وضعیت رزرو</th>
                    <th>پرداخت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($reservations as $reservation)
                    <tr>
                        <td>{{ $reservation->student?->full_name ?: 'نامشخص' }}</td>
                        <td class="ltr">{{ $reservation->student?->phones->pluck('phone')->implode(' / ') }}</td>
                        <td>
                            @if($reservation->slot)
                                {{ \App\Support\PersianDate::date($reservation->slot->date) }}
                                <span class="text-muted">
                                    {{ \App\Support\PersianDate::time($reservation->assignedStartTime()) }}
                                    تا
                                    {{ \App\Support\PersianDate::time($reservation->assignedEndTime()) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name }}</td>
                        <td><span class="badge text-bg-light">{{ $reservation->status->label() }}</span></td>
                        <td>{{ $reservation->payment?->status?->label() ?: '-' }}</td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reservations.show', $reservation) }}">جزئیات</a>
                            @can('update_reservations')
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.reservations.edit', $reservation) }}">ویرایش</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">رزروی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $reservations->links() }}</div>
    </div>
@endsection

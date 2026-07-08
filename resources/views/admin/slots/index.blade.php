@extends('layouts.admin')

@section('title', 'تایمها')

@section('actions')
    @can('create_slots')
        <a class="btn btn-primary" href="{{ route('admin.slots.create') }}">ایجاد تایم</a>
    @endcan
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="get">
                <div class="col-md-3">
                    <label class="form-label">تاریخ</label>
                    <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(request('date')) }}" class="form-control jalali-date-picker" autocomplete="off">
                </div>
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
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-outline-primary">فیلتر</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}">پاکسازی</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>ساعت</th>
                    <th>مشاور</th>
                    <th>ظرفیت</th>
                    <th>رزرو فعال</th>
                    <th>وضعیت تایم</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($slots as $slot)
                    @php
                        $activeCount = $availability->countActiveReservations($slot);
                        $isAvailable = $availability->isAvailable($slot);
                    @endphp
                    <tr>
                        <td>{{ \App\Support\PersianDate::date($slot->date) }}</td>
                        <td>{{ \App\Support\PersianDate::time($slot->start_time) }} تا {{ \App\Support\PersianDate::time($slot->end_time) }}</td>
                        <td>{{ $slot->advisor?->name }}</td>
                        <td>{{ \App\Support\PersianDate::number($slot->capacity) }}</td>
                        <td>{{ \App\Support\PersianDate::number($activeCount) }}</td>
                        <td>
                            @if($slot->status->value !== 'active')
                                <span class="badge text-bg-secondary">غیرفعال</span>
                            @elseif($isAvailable)
                                <span class="badge text-bg-success">آزاد</span>
                            @else
                                <span class="badge text-bg-warning">رزرو شده / قفل</span>
                            @endif
                        </td>
                        <td class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-info" href="{{ route('admin.slots.show', $slot) }}">مشاهده</a>
                            @can('update_slots')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.slots.edit', $slot) }}">ویرایش</a>
                            @endcan
                            @can('delete_slots')
                                <form method="post" action="{{ route('admin.slots.destroy', $slot) }}" onsubmit="return confirm('حذف شود؟')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">تایمی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $slots->links() }}</div>
    </div>
@endsection

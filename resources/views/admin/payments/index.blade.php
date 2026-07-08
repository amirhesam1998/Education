@extends('layouts.admin')

@section('title', 'فیشهای پرداخت')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="get">
                <div class="col-md-4">
                    <label class="form-label">وضعیت پرداخت</label>
                    <select name="status" class="form-select">
                        <option value="">در انتظار تأیید فیش</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-outline-primary">فیلتر</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.payments.index') }}">پاکسازی</a>
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
                    <th>مبلغ</th>
                    <th>زمان آپلود</th>
                    <th>رزرو</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->reservation?->student?->full_name ?: '-' }}</td>
                        <td>{{ \App\Support\PersianDate::money($payment->amount) }}</td>
                        <td>{{ \App\Support\PersianDate::dateTime($payment->uploaded_at) }}</td>
                        <td>
                            @if($payment->reservation?->slot)
                                {{ \App\Support\PersianDate::date($payment->reservation->slot->date) }}
                                {{ \App\Support\PersianDate::time($payment->reservation->assignedStartTime()) }}
                                تا
                                {{ \App\Support\PersianDate::time($payment->reservation->assignedEndTime()) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $payment->status->label() }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.payments.show', $payment) }}">بررسی</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">فیشی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $payments->links() }}</div>
    </div>
@endsection

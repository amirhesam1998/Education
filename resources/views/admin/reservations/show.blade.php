@extends('layouts.admin')

@section('title', 'جزئیات رزرو')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.index') }}">بازگشت</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            @php($payment = $reservation->payment)
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">اطلاعات رزرو</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>وضعیت:</strong> {{ $reservation->status->label() }}</div>
                        <div class="col-md-6"><strong>مشاور:</strong> {{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name }}</div>
                        <div class="col-md-6"><strong>تاریخ:</strong> {{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date) : '-' }}</div>
                        <div class="col-md-6"><strong>بازه کلی تایم:</strong> {{ $reservation->slot ? \App\Support\PersianDate::time($reservation->slot->start_time).' تا '.\App\Support\PersianDate::time($reservation->slot->end_time) : '-' }}</div>
                        <div class="col-md-6"><strong>زمان رزرو:</strong> {{ $reservation->slot ? \App\Support\PersianDate::time($reservation->assignedStartTime()).' تا '.\App\Support\PersianDate::time($reservation->assignedEndTime()) : '-' }}</div>
                        <div class="col-md-6"><strong>پیش پرداخت:</strong> {{ $reservation->prepayment_required ? 'نیاز دارد' : 'نیاز ندارد' }}</div>
                        <div class="col-md-6"><strong>مبلغ:</strong> {{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                        <div class="col-md-6"><strong>مهلت پرداخت:</strong> {{ \App\Support\PersianDate::dateTime($reservation->payment_deadline_at) }}</div>
                        <div class="col-md-6"><strong>مهلت لینک:</strong> {{ \App\Support\PersianDate::dateTime($reservation->public_token_expires_at) }}</div>
                        <div class="col-md-6"><strong>زمان تأیید:</strong> {{ \App\Support\PersianDate::dateTime($reservation->confirmed_at) }}</div>
                        <div class="col-md-6"><strong>زمان لغو:</strong> {{ \App\Support\PersianDate::dateTime($reservation->cancelled_at) }}</div>
                        <div class="col-md-6"><strong>زمان انقضا:</strong> {{ \App\Support\PersianDate::dateTime($reservation->expired_at) }}</div>
                        <div class="col-md-6"><strong>زمان پایان:</strong> {{ \App\Support\PersianDate::dateTime($reservation->completed_at) }}</div>
                        <div class="col-md-6"><strong>وضعیت پرداخت:</strong> {{ $reservation->payment?->status?->label() ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">پرداخت و فیش پیش پرداخت</h2>
                    @if($reservation->prepayment_required)
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><strong>مبلغ پیش پرداخت:</strong> {{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                            <div class="col-md-6"><strong>وضعیت پرداخت:</strong> {{ $payment?->status?->label() ?: '-' }}</div>
                            <div class="col-md-6"><strong>زمان آپلود فیش:</strong> {{ \App\Support\PersianDate::dateTime($payment?->uploaded_at) }}</div>
                            <div class="col-md-6"><strong>زمان تأیید:</strong> {{ \App\Support\PersianDate::dateTime($payment?->approved_at) }}</div>
                            <div class="col-md-6"><strong>زمان رد:</strong> {{ \App\Support\PersianDate::dateTime($payment?->rejected_at) }}</div>
                            <div class="col-md-6"><strong>تأیید کننده:</strong> {{ $payment?->approver?->name ?: '-' }}</div>
                            @if($payment?->rejection_reason)
                                <div class="col-12"><strong>دلیل رد:</strong> {{ $payment->rejection_reason }}</div>
                            @endif
                        </div>

                        @if($payment?->receipt_image_path)
                            <div class="mb-3">
                                <img src="{{ route('admin.reservations.receipt', $reservation) }}" alt="فیش پیش پرداخت" class="img-fluid rounded border">
                            </div>

                            <div class="row g-2">
                                @can('approve_payments')
                                    <div class="col-md-6">
                                        <form method="post" action="{{ route('admin.payments.approve', $payment) }}">
                                            @csrf
                                            <button class="btn btn-success w-100">تأیید فیش</button>
                                        </form>
                                    </div>
                                @endcan
                                @can('reject_payments')
                                    <div class="col-md-6">
                                        <form method="post" action="{{ route('admin.payments.reject', $payment) }}">
                                            @csrf
                                            <textarea name="rejection_reason" rows="2" class="form-control mb-2" placeholder="دلیل رد فیش" required>{{ old('rejection_reason') }}</textarea>
                                            <button class="btn btn-outline-danger w-100">رد فیش</button>
                                        </form>
                                    </div>
                                @endcan
                            </div>
                        @else
                            <div class="text-muted">فیشی توسط دانش آموز آپلود نشده است.</div>
                        @endif
                    @else
                        <div class="text-muted">برای این رزرو پیش پرداخت لازم نیست.</div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">دانش آموز</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>نام و نام خانوادگی:</strong> {{ $reservation->student?->full_name ?: '-' }}</div>
                        <div class="col-md-6"><strong>رشته:</strong> {{ $reservation->student?->major ?: '-' }}</div>
                        <div class="col-md-6"><strong>تراز:</strong> {{ $reservation->student?->score ?: '-' }}</div>
                        <div class="col-md-6"><strong>نوع کنکور:</strong> {{ $reservation->student?->exam_type ?: '-' }}</div>
                        <div class="col-12"><strong>شمارهها:</strong> <span class="ltr">{{ $reservation->student?->phones->pluck('phone')->implode(' / ') }}</span></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">تاریخچه فعالیت</h2>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>زمان</th><th>کاربر</th><th>عملیات</th><th>توضیح</th></tr></thead>
                            <tbody>
                            @forelse($reservation->activityLogs->sortByDesc('created_at') as $log)
                                <tr>
                                    <td>{{ \App\Support\PersianDate::dateTime($log->created_at) }}</td>
                                    <td>{{ $log->user?->name ?: 'سیستم' }}</td>
                                    <td>{{ $log->action }}</td>
                                    <td>{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center">فعالیتی ثبت نشده است.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">لینک دانش آموز</h2>
                    <input class="form-control ltr mb-2" id="public-link" value="{{ $publicUrl }}" readonly>
                    <button class="btn btn-outline-primary w-100" type="button" onclick="navigator.clipboard.writeText(document.getElementById('public-link').value)">کپی لینک</button>
                    @can('update_reservations')
                        <form method="post" action="{{ route('admin.reservations.regenerate-link', $reservation) }}" class="mt-2">
                            @csrf
                            <button class="btn btn-outline-secondary w-100">ساخت لینک جدید</button>
                        </form>
                    @endcan
                </div>
            </div>

            @can('change_reservation_slot')
                <div class="card mb-3">
                    <div class="card-body">
                        <h2 class="h5 mb-3">تغییر تایم</h2>
                        <form method="post" action="{{ route('admin.reservations.change-slot', $reservation) }}">
                            @csrf
                            <select name="slot_id" class="form-select mb-2" required>
                                <option value="">تایم جدید</option>
                                @foreach($availableSlots as $slot)
                                    <option value="{{ $slot->id }}">
                                        {{ \App\Support\PersianDate::date($slot->date) }}
                                        - {{ \App\Support\PersianDate::time($slot->start_time) }} تا {{ \App\Support\PersianDate::time($slot->end_time) }}
                                        - {{ $slot->advisor?->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-warning w-100">تغییر تایم</button>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">عملیات</h2>
                    @can('update_reservations')
                        <a class="btn btn-outline-primary w-100 mb-2" href="{{ route('admin.reservations.edit', $reservation) }}">ویرایش</a>
                    @endcan
                    @can('confirm_reservations')
                        <form method="post" action="{{ route('admin.reservations.complete', $reservation) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success w-100">انجام شده</button>
                        </form>
                        <form method="post" action="{{ route('admin.reservations.no-show', $reservation) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-outline-dark w-100">عدم حضور</button>
                        </form>
                    @endcan
                    @can('cancel_reservations')
                        <form method="post" action="{{ route('admin.reservations.cancel', $reservation) }}">
                            @csrf
                            <textarea name="reason" class="form-control mb-2" rows="2" placeholder="دلیل لغو"></textarea>
                            <button class="btn btn-outline-danger w-100">لغو رزرو</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

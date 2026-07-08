@extends('layouts.admin')

@section('title', 'بررسی فیش پرداخت')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.payments.index') }}">بازگشت</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">تصویر فیش</h2>
                    @if($payment->receipt_image_path)
                        <img src="{{ route('admin.payments.receipt', $payment) }}" alt="فیش پرداخت" class="img-fluid rounded border">
                    @else
                        <div class="text-muted">تصویری ثبت نشده است.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">اطلاعات پرداخت</h2>
                    <div class="mb-2"><strong>وضعیت:</strong> {{ $payment->status->label() }}</div>
                    <div class="mb-2"><strong>مبلغ:</strong> {{ \App\Support\PersianDate::money($payment->amount) }}</div>
                    <div class="mb-2"><strong>آپلود:</strong> {{ \App\Support\PersianDate::dateTime($payment->uploaded_at) }}</div>
                    <div class="mb-2"><strong>تأیید:</strong> {{ \App\Support\PersianDate::dateTime($payment->approved_at) }}</div>
                    <div class="mb-2"><strong>رد:</strong> {{ \App\Support\PersianDate::dateTime($payment->rejected_at) }}</div>
                    <div class="mb-2"><strong>تأیید کننده:</strong> {{ $payment->approver?->name ?: '-' }}</div>
                    <div class="mb-2"><strong>دلیل رد:</strong> {{ $payment->rejection_reason ?: '-' }}</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">رزرو</h2>
                    <div class="mb-2"><strong>دانش آموز:</strong> {{ $payment->reservation?->student?->full_name ?: '-' }}</div>
                    <div class="mb-2"><strong>شماره:</strong> <span class="ltr">{{ $payment->reservation?->student?->phones->pluck('phone')->implode(' / ') }}</span></div>
                    <div class="mb-2"><strong>مشاور:</strong> {{ $payment->reservation?->advisor?->name ?: $payment->reservation?->slot?->advisor?->name }}</div>
                    <div class="mb-2"><strong>تاریخ:</strong> {{ $payment->reservation?->slot ? \App\Support\PersianDate::date($payment->reservation->slot->date) : '-' }}</div>
                    <div class="mb-2"><strong>زمان رزرو:</strong> {{ $payment->reservation?->slot ? \App\Support\PersianDate::time($payment->reservation->assignedStartTime()).' تا '.\App\Support\PersianDate::time($payment->reservation->assignedEndTime()) : '-' }}</div>
                    <a class="btn btn-outline-primary w-100" href="{{ route('admin.reservations.show', $payment->reservation) }}">مشاهده رزرو</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">عملیات</h2>
                    @can('approve_payments')
                        <form method="post" action="{{ route('admin.payments.approve', $payment) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success w-100">تأیید فیش</button>
                        </form>
                    @endcan
                    @can('reject_payments')
                        <form method="post" action="{{ route('admin.payments.reject', $payment) }}">
                            @csrf
                            <textarea name="rejection_reason" rows="3" class="form-control mb-2" placeholder="دلیل رد فیش" required>{{ old('rejection_reason') }}</textarea>
                            <button class="btn btn-outline-danger w-100">رد فیش</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

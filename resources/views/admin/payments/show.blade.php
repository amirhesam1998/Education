@extends('layouts.admin')

@section('title', 'بررسی فیش پرداخت')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.payments.index') }}">
        <i class="ri-arrow-right-line align-middle"></i> بازگشت
    </a>
@endsection

@push('styles')
    <style>
        /* ===========================================================
           Payment review — page-local styles
        =========================================================== */
        .card-section {
            margin-bottom: 1rem;
        }

        .card-section .card-header {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .95rem;
        }

        .card-section .card-header i {
            color: var(--brand-600);
            font-size: 1.05rem;
        }

        .card-section .card-body {
            padding: 1.25rem;
        }

        .receipt-frame {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--bg);
        }

        .receipt-frame img {
            display: block;
            width: 100%;
        }

        .info-list .info-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: .55rem 0;
            border-bottom: 1px solid var(--border);
            font-size: .87rem;
        }

        .info-list .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-list .info-row:first-child {
            padding-top: 0;
        }

        .info-list .info-key {
            color: var(--ink-500);
            flex-shrink: 0;
        }

        .info-list .info-val {
            color: var(--ink-900);
            font-weight: 600;
            text-align: left;
        }

        .badge.text-bg-light {
            background: var(--ink-100) !important;
            color: var(--ink-700) !important;
            border: 1px solid var(--border);
        }

        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--ink-500);
            font-size: .85rem;
        }

        .empty-state i {
            font-size: 1.8rem;
            color: var(--ink-300);
            margin-bottom: .5rem;
            display: block;
        }

        /* Buttons — align every variant with the design system's radius / weight */
        .btn {
            border-radius: var(--radius-sm);
            font-weight: 500;
        }

        .btn-success {
            background: var(--success);
            border-color: var(--success);
        }

        .btn-success:hover {
            filter: brightness(.94);
        }

        .btn-outline-danger {
            color: var(--danger);
            border-color: #f0c8c4;
        }

        .btn-outline-danger:hover {
            background: var(--danger);
            border-color: var(--danger);
        }

        .side-card .card-body {
            padding: 1.1rem;
        }

        .side-card h2 {
            font-size: .92rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .9rem;
        }

        .side-card h2 i {
            color: var(--brand-600);
        }

        @media (max-width: 575.98px) {
            .info-list .info-row {
                flex-direction: column;
                gap: .15rem;
            }

            .info-list .info-val {
                text-align: right;
            }
        }
    </style>
@endpush

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card card-section mb-0">
                <div class="card-header"><i class="ri-image-line"></i> تصویر فیش</div>
                <div class="card-body">
                    @if($canViewReceipt && $payment->receipt_image_path)
                        <div class="receipt-frame">
                            <img src="{{ route('admin.payments.receipt', $payment) }}" alt="فیش پرداخت">
                        </div>
                    @elseif($canViewReceipt)
                        <div class="empty-state">
                            <i class="ri-image-line"></i>
                            تصویری ثبت نشده است
                        </div>
                    @else
                        <div class="empty-state">شما دسترسی مشاهده فیش پیش‌پرداخت را ندارید.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- Payment info --}}
            <div class="card side-card card-section">
                <div class="card-body">
                    <h2><i class="ri-bank-card-line"></i> اطلاعات پرداخت</h2>
                    <div class="info-list">
                        <div class="info-row"><span class="info-key">وضعیت</span><span class="info-val"><span
                                    class="badge text-bg-light">{{ $payment->status->label() }}</span></span></div>
                        <div class="info-row"><span class="info-key">مبلغ</span><span
                                class="info-val">{{ \App\Support\PersianDate::money($payment->amount) }}</span></div>
                        <div class="info-row"><span class="info-key">آپلود</span><span
                                class="info-val">{{ \App\Support\PersianDate::dateTime($payment->uploaded_at) }}</span>
                        </div>
                        <div class="info-row"><span class="info-key">تأیید</span><span
                                class="info-val">{{ \App\Support\PersianDate::dateTime($payment->approved_at) }}</span>
                        </div>
                        <div class="info-row"><span class="info-key">رد</span><span
                                class="info-val">{{ \App\Support\PersianDate::dateTime($payment->rejected_at) }}</span>
                        </div>
                        <div class="info-row"><span class="info-key">تأیید کننده</span><span
                                class="info-val">{{ $payment->approver?->name ?: '-' }}</span></div>
                        <div class="info-row"><span class="info-key">دلیل رد</span><span
                                class="info-val">{{ $payment->rejection_reason ?: '-' }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Reservation summary --}}
            <div class="card side-card card-section">
                <div class="card-body">
                    <h2><i class="ri-file-list-3-line"></i> رزرو</h2>
                    <div class="info-list mb-3">
                        @if($canViewPersonalData)
                            <div class="info-row"><span class="info-key">دانش آموز</span><span
                                    class="info-val">{{ $payment->reservation?->student?->full_name ?: '-' }}</span></div>
                            <div class="info-row"><span class="info-key">شماره</span><span
                                    class="info-val ltr">{{ $payment->reservation?->student?->phones->pluck('phone')->implode(' / ') }}</span>
                            </div>
                        @else
                            <div class="info-row"><span class="info-key">رزرو</span><span class="info-val">#{{ $payment->reservation_id }}</span></div>
                        @endif
                        <div class="info-row"><span class="info-key">مشاور</span><span
                                class="info-val">{{ $payment->reservation?->advisor?->name ?: $payment->reservation?->slot?->advisor?->name }}</span>
                        </div>
                        <div class="info-row"><span class="info-key">تاریخ</span><span
                                class="info-val">{{ $payment->reservation?->slot ? \App\Support\PersianDate::date($payment->reservation->slot->date) : '-' }}</span>
                        </div>
                        <div class="info-row"><span class="info-key">زمان رزرو</span><span
                                class="info-val ltr">{{ $payment->reservation?->slot ? \App\Support\PersianDate::time($payment->reservation->assignedStartTime()) . ' - ' . \App\Support\PersianDate::time($payment->reservation->assignedEndTime()) : '-' }}</span>
                        </div>
                    </div>
                    <a class="btn btn-outline-primary w-100"
                        href="{{ route('admin.reservations.show', $payment->reservation) }}">
                        <i class="ri-eye-line align-middle"></i> مشاهده رزرو
                    </a>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card side-card card-section mb-0">
                <div class="card-body">
                    <h2><i class="ri-tools-line"></i> عملیات</h2>
                    @if($canViewReceipt)
                    @can('approve_payments')
                        <form method="post" action="{{ route('admin.payments.approve', $payment) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success w-100">
                                <i class="ri-checkbox-circle-line align-middle"></i> تأیید فیش
                            </button>
                        </form>
                    @endcan
                    @can('reject_payments')
                        <form method="post" action="{{ route('admin.payments.reject', $payment) }}">
                            @csrf
                            <textarea name="rejection_reason" rows="3" class="form-control mb-2" placeholder="دلیل رد فیش"
                                required>{{ old('rejection_reason') }}</textarea>
                            <button class="btn btn-outline-danger w-100">
                                <i class="ri-close-circle-line align-middle"></i> رد فیش
                            </button>
                        </form>
                    @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

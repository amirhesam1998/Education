@extends('layouts.admin')

@section('title', 'جزئیات رزرو')

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.index') }}">
        <i class="ri-arrow-right-line align-middle"></i> بازگشت
    </a>
@endsection

@push('styles')
<style>
    /* ===========================================================
       Reservation detail — page-local styles
    =========================================================== */
    .card-section{ margin-bottom: 1rem; }
    .card-section .card-header{
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .95rem;
    }
    .card-section .card-header i{ color: var(--brand-600); font-size: 1.05rem; }
    .card-section .card-body{ padding: 1.25rem; }

    .info-grid{
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .85rem;
    }
    .info-item{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .8rem .95rem;
        background: var(--bg);
    }
    .info-label{
        font-size: .74rem;
        color: var(--ink-500);
        margin-bottom: .2rem;
    }
    .info-value{
        font-size: .87rem;
        font-weight: 600;
        color: var(--ink-900);
    }

    .badge.text-bg-light{
        background: var(--ink-100) !important;
        color: var(--ink-700) !important;
        border: 1px solid var(--border);
    }

    .receipt-frame{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        overflow: hidden;
        background: var(--bg);
    }
    .receipt-frame img{ display: block; width: 100%; }

    .rejection-note{
        background: #fbe6e4;
        color: var(--danger);
        border-radius: var(--radius-sm);
        padding: .75rem .9rem;
        font-size: .85rem;
    }

    /* Buttons — align every variant with the design system's radius / weight */
    .btn{ border-radius: var(--radius-sm); font-weight: 500; }
    .btn-success{ background: var(--success); border-color: var(--success); }
    .btn-success:hover{ filter: brightness(.94); }
    .btn-warning{ background: var(--warning); border-color: var(--warning); color: #fff; }
    .btn-warning:hover{ filter: brightness(.94); color: #fff; }
    .btn-outline-danger{ color: var(--danger); border-color: #f0c8c4; }
    .btn-outline-danger:hover{ background: var(--danger); border-color: var(--danger); }
    .btn-outline-dark{ color: var(--ink-700); border-color: var(--ink-300); }
    .btn-outline-dark:hover{ background: var(--ink-900); border-color: var(--ink-900); }
    .btn-outline-info{ color: var(--info); border-color: #c7dbf5; }
    .btn-outline-info:hover{ background: var(--info); border-color: var(--info); }

    /* Sidebar */
    .side-card .card-body{ padding: 1.1rem; }
    .side-card h2{ font-size: .92rem; font-weight: 700; display: flex; align-items: center; gap: .4rem; margin-bottom: .9rem; }
    .side-card h2 i{ color: var(--brand-600); }

    #public-link{ font-size: .82rem; }

    .activity-table th, .activity-table td{ font-size: .8rem; }

    .empty-state{
        text-align: center;
        padding: 1.75rem 1rem;
        color: var(--ink-500);
        font-size: .85rem;
    }

    @media (max-width: 991.98px){
        .info-grid{ grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575.98px){
        .info-grid{ grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            @php($payment = $reservation->payment)

            {{-- Reservation info --}}
            <div class="card card-section">
                <div class="card-header"><i class="ri-information-line"></i> اطلاعات رزرو</div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">وضعیت</div>
                            <div class="info-value"><span class="badge text-bg-light">{{ $reservation->status->label() }}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مشاور</div>
                            <div class="info-value">{{ $reservation->advisor?->name ?: $reservation->slot?->advisor?->name }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">تاریخ</div>
                            <div class="info-value">{{ $reservation->slot ? \App\Support\PersianDate::date($reservation->slot->date) : '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">بازه کلی تایم</div>
                            <div class="info-value ltr">{{ $reservation->slot ? \App\Support\PersianDate::time($reservation->slot->start_time).' - '.\App\Support\PersianDate::time($reservation->slot->end_time) : '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان رزرو</div>
                            <div class="info-value ltr">{{ $reservation->slot ? \App\Support\PersianDate::time($reservation->assignedStartTime()).' - '.\App\Support\PersianDate::time($reservation->assignedEndTime()) : '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">پیش پرداخت</div>
                            <div class="info-value">{{ $reservation->prepayment_required ? 'نیاز دارد' : 'نیاز ندارد' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مبلغ</div>
                            <div class="info-value">{{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مهلت پرداخت</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->payment_deadline_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">مهلت لینک</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->public_token_expires_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان تأیید</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->confirmed_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان لغو</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->cancelled_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان انقضا</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->expired_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">زمان پایان</div>
                            <div class="info-value">{{ \App\Support\PersianDate::dateTime($reservation->completed_at) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">وضعیت پرداخت</div>
                            <div class="info-value">{{ $reservation->payment?->status?->label() ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment / receipt --}}
            <div class="card card-section">
                <div class="card-header"><i class="ri-bank-card-line"></i> پرداخت و فیش پیش پرداخت</div>
                <div class="card-body">
                    @if($reservation->prepayment_required)
                        <div class="info-grid mb-3">
                            <div class="info-item">
                                <div class="info-label">مبلغ پیش پرداخت</div>
                                <div class="info-value">{{ \App\Support\PersianDate::money($reservation->prepayment_amount) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">وضعیت پرداخت</div>
                                <div class="info-value">{{ $payment?->status?->label() ?: '-' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">زمان آپلود فیش</div>
                                <div class="info-value">{{ \App\Support\PersianDate::dateTime($payment?->uploaded_at) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">زمان تأیید</div>
                                <div class="info-value">{{ \App\Support\PersianDate::dateTime($payment?->approved_at) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">زمان رد</div>
                                <div class="info-value">{{ \App\Support\PersianDate::dateTime($payment?->rejected_at) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">تأیید کننده</div>
                                <div class="info-value">{{ $payment?->approver?->name ?: '-' }}</div>
                            </div>
                        </div>

                        @if($payment?->rejection_reason)
                            <div class="rejection-note mb-3">
                                <strong>دلیل رد:</strong> {{ $payment->rejection_reason }}
                            </div>
                        @endif

                        @if($payment?->receipt_image_path)
                            <div class="receipt-frame mb-3">
                                <img src="{{ route('admin.reservations.receipt', $reservation) }}" alt="فیش پیش پرداخت">
                            </div>

                            <div class="row g-2">
                                @can('approve_payments')
                                    <div class="col-md-6">
                                        <form method="post" action="{{ route('admin.payments.approve', $payment) }}">
                                            @csrf
                                            <button class="btn btn-success w-100">
                                                <i class="ri-checkbox-circle-line align-middle"></i> تأیید فیش
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                                @can('reject_payments')
                                    <div class="col-md-6">
                                        <form method="post" action="{{ route('admin.payments.reject', $payment) }}">
                                            @csrf
                                            <textarea name="rejection_reason" rows="2" class="form-control mb-2" placeholder="دلیل رد فیش" required>{{ old('rejection_reason') }}</textarea>
                                            <button class="btn btn-outline-danger w-100">
                                                <i class="ri-close-circle-line align-middle"></i> رد فیش
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="ri-image-line d-block mb-1" style="font-size:1.6rem;color:var(--ink-300)"></i>
                                فیشی توسط دانش آموز آپلود نشده است
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="ri-checkbox-circle-line d-block mb-1" style="font-size:1.6rem;color:var(--ink-300)"></i>
                            برای این رزرو پیش پرداخت لازم نیست
                        </div>
                    @endif
                </div>
            </div>

            {{-- Student --}}
            <div class="card card-section">
                <div class="card-header"><i class="ri-graduation-cap-line"></i> دانش آموز</div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">نام و نام خانوادگی</div>
                            <div class="info-value">{{ $reservation->student?->full_name ?: '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">رشته</div>
                            <div class="info-value">{{ $reservation->student?->major ?: '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">تراز</div>
                            <div class="info-value">{{ $reservation->student?->score ?: '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">نوع کنکور</div>
                            <div class="info-value">{{ $reservation->student?->exam_type ?: '-' }}</div>
                        </div>
                        <div class="info-item" style="grid-column: span 2;">
                            <div class="info-label">شمارهها</div>
                            <div class="info-value ltr">{{ $reservation->student?->phones->pluck('phone')->implode(' / ') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Activity log --}}
            <div class="card card-section mb-0">
                <div class="card-header"><i class="ri-history-line"></i> تاریخچه فعالیت</div>
                <div class="table-responsive">
                    <table class="table table-hover activity-table mb-0">
                        <thead><tr><th>زمان</th><th>کاربر</th><th>عملیات</th><th>توضیح</th></tr></thead>
                        <tbody>
                        @forelse($reservation->activityLogs->sortByDesc('created_at') as $log)
                            <tr>
                                <td class="text-nowrap">{{ \App\Support\PersianDate::dateTime($log->created_at) }}</td>
                                <td>{{ $log->user?->name ?: 'سیستم' }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state">فعالیتی ثبت نشده است</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Public link --}}
            <div class="card side-card card-section">
                <div class="card-body">
                    <h2><i class="ri-links-line"></i> لینک دانش آموز</h2>
                    <input class="form-control ltr mb-2" id="public-link" value="{{ $publicUrl }}" readonly>
                    <button class="btn btn-outline-primary w-100" type="button" onclick="navigator.clipboard.writeText(document.getElementById('public-link').value)">
                        <i class="ri-file-copy-line align-middle"></i> کپی لینک
                    </button>
                    @can('update_reservations')
                        <form method="post" action="{{ route('admin.reservations.regenerate-link', $reservation) }}" class="mt-2">
                            @csrf
                            <button class="btn btn-outline-secondary w-100">
                                <i class="ri-refresh-line align-middle"></i> ساخت لینک جدید
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Change slot --}}
            @can('change_reservation_slot')
                <div class="card side-card card-section">
                    <div class="card-body">
                        <h2><i class="ri-calendar-2-line"></i> تغییر تایم</h2>
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
                            <button class="btn btn-warning w-100">
                                <i class="ri-exchange-line align-middle"></i> تغییر تایم
                            </button>
                        </form>
                    </div>
                </div>
            @endcan

            {{-- Actions --}}
            <div class="card side-card card-section mb-0">
                <div class="card-body">
                    <h2><i class="ri-tools-line"></i> عملیات</h2>
                    @can('update_reservations')
                        <a class="btn btn-outline-primary w-100 mb-2" href="{{ route('admin.reservations.edit', $reservation) }}">
                            <i class="ri-edit-line align-middle"></i> ویرایش
                        </a>
                    @endcan
                    @can('confirm_reservations')
                        <form method="post" action="{{ route('admin.reservations.complete', $reservation) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success w-100">
                                <i class="ri-checkbox-circle-line align-middle"></i> انجام شده
                            </button>
                        </form>
                        <form method="post" action="{{ route('admin.reservations.no-show', $reservation) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-outline-dark w-100">
                                <i class="ri-user-unfollow-line align-middle"></i> عدم حضور
                            </button>
                        </form>
                    @endcan
                    @can('cancel_reservations')
                        <form method="post" action="{{ route('admin.reservations.cancel', $reservation) }}">
                            @csrf
                            <textarea name="reason" class="form-control mb-2" rows="2" placeholder="دلیل لغو"></textarea>
                            <button class="btn btn-outline-danger w-100">
                                <i class="ri-close-circle-line align-middle"></i> لغو رزرو
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

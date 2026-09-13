@extends('layouts.admin')

@section('title', 'فیشهای پرداخت')

@push('styles')
<style>
    /* ===========================================================
       Payments index — page-local styles
    =========================================================== */
    .filter-card .form-label{
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-500);
        margin-bottom: .35rem;
    }
    .filter-card .btn{ height: 40px; }

    .student-cell{ font-weight: 600; color: var(--ink-900); font-size: .87rem; }

    .amount-cell{
        font-weight: 700;
        color: var(--brand-700);
        font-size: .87rem;
        white-space: nowrap;
    }

    .reservation-cell .res-date{ font-weight: 600; font-size: .84rem; }
    .reservation-cell .res-time{ font-size: .78rem; color: var(--ink-500); direction: ltr; text-align: right; }

    .badge.text-bg-light{
        background: var(--ink-100) !important;
        color: var(--ink-700) !important;
        border: 1px solid var(--border);
    }

    .row-actions .btn{
        border-radius: var(--radius-sm);
        font-size: .78rem;
    }

    .pagination-wrap{
        display: flex;
        justify-content: center;
        padding: .9rem;
    }
    .pagination{ margin: 0; }
    .page-link{
        border: 1px solid var(--border);
        color: var(--ink-700);
        border-radius: var(--radius-sm) !important;
        margin: 0 .15rem;
    }
    .page-item.active .page-link{
        background: var(--brand-500);
        border-color: var(--brand-500);
    }

    .empty-state{
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }
    .empty-state i{ font-size: 2.2rem; color: var(--ink-300); margin-bottom: .5rem; display: block; }

    @media (max-width: 767.98px){
        .filter-card .col-md-4{ margin-bottom: .5rem; }
    }
</style>
@endpush

@section('content')

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-4 col-sm-8">
                    <label class="form-label"><i class="ri-flag-line align-middle"></i> وضعیت پرداخت</label>
                    <select name="status" class="form-select">
                        <option value="">در انتظار تأیید فیش</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-sm-4 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1">
                        <i class="ri-filter-3-line align-middle"></i> فیلتر
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.payments.index') }}" title="پاکسازی">
                        <i class="ri-refresh-line align-middle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
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
                        <td class="student-cell">{{ $payment->reservation?->student?->full_name ?: 'رزرو #'.($payment->reservation_id ?: '-') }}</td>
                        <td class="amount-cell">{{ \App\Support\PersianDate::money($payment->amount) }}</td>
                        <td class="text-nowrap">{{ \App\Support\PersianDate::dateTime($payment->uploaded_at) }}</td>
                        <td class="reservation-cell">
                            @if($payment->reservation?->slot)
                                <div class="res-date">{{ \App\Support\PersianDate::date($payment->reservation->slot->date) }}</div>
                                <div class="res-time">
                                    {{ \App\Support\PersianDate::time($payment->reservation->assignedStartTime()) }}
                                    —
                                    {{ \App\Support\PersianDate::time($payment->reservation->assignedEndTime()) }}
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><span class="badge text-bg-light">{{ $payment->status->label() }}</span></td>
                        <td>
                            <div class="row-actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.payments.show', $payment) }}">
                                    <i class="ri-search-eye-line align-middle"></i> بررسی
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="ri-bank-card-line"></i>
                                فیشی یافت نشد
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
            <div class="pagination-wrap">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection

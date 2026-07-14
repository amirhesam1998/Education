@extends('layouts.admin')

@section('title', 'تایم‌ها')

@section('actions')
    @can('create_slots')
        <a class="btn btn-primary" href="{{ route('admin.slots.create') }}">
            <i class="ri-add-line align-middle"></i> ایجاد تایم
        </a>
    @endcan
@endsection

@push('styles')
<style>
    .filter-card .card-body{ padding: 1.1rem 1.25rem; }
    .filter-card .form-label{
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-500);
        margin-bottom: .35rem;
    }
    .filter-card .btn{ height: 40px; }

    .row-actions{ display: flex; gap: .4rem; flex-wrap: nowrap; justify-content: end; }
    .row-actions .btn{
        width: 34px; height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm);
        font-size: 1rem;
    }
    .row-actions form{ margin: 0; }

    .badge.text-bg-secondary{ background: var(--ink-100) !important; color: var(--ink-700) !important; }
    .badge.text-bg-success{ background: #e2f5ec !important; color: var(--success) !important; }
    .badge.text-bg-warning{ background: #fbf1de !important; color: var(--warning) !important; }

    .slot-date-group{ margin-bottom: 1rem; }
    .slot-date-head{
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        background: var(--surface);
    }
    .slot-date-title{ font-weight: 800; color: var(--ink-900); }
    .slot-date-meta{ font-size: .8rem; color: var(--ink-500); margin-top: .2rem; }
    .slot-date-counts{ display: flex; flex-wrap: wrap; gap: .45rem; justify-content: end; }
    .slot-interval-list{ display: grid; gap: .55rem; padding: 1rem 1.25rem; }
    .slot-interval-row{
        display: grid;
        grid-template-columns: minmax(130px, 1fr) minmax(110px, 1fr) minmax(100px, auto) auto;
        gap: .75rem;
        align-items: center;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .65rem .75rem;
        background: var(--bg);
    }

    .empty-state{
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }
    .empty-state i{ font-size: 2.2rem; color: var(--ink-300); margin-bottom: .5rem; display: block; }

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

    @media (max-width: 767.98px){
        .filter-card .col-md-3{ margin-bottom: .5rem; }
        .row-actions{ flex-wrap: wrap; justify-content: start; }
        .slot-date-head{ flex-direction: column; }
        .slot-date-counts{ justify-content: start; }
        .slot-interval-row{ grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="ri-calendar-line align-middle"></i> تاریخ</label>
                    <input type="text" name="date" value="{{ \App\Support\PersianDate::inputDate(request('date')) }}" class="form-control jalali-date-picker" autocomplete="off" placeholder="انتخاب تاریخ">
                </div>
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
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1">
                        <i class="ri-filter-3-line align-middle"></i> فیلتر
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.slots.index') }}" title="پاکسازی">
                        <i class="ri-refresh-line align-middle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @forelse($slotDateGroups as $dateGroup)
        <div class="card slot-date-group">
            <div class="slot-date-head">
                <div>
                    <div class="slot-date-title">{{ $dateGroup['jalali_date'] }}</div>
                    <div class="slot-date-meta">{{ $dateGroup['weekday_label'] }} · {{ $dateGroup['advisor_label'] ?: 'همه مشاوران' }}</div>
                </div>
                <div class="slot-date-counts">
                    <span class="badge text-bg-secondary">کل: {{ \App\Support\PersianDate::number($dateGroup['total_count']) }}</span>
                    <span class="badge text-bg-success">آزاد: {{ \App\Support\PersianDate::number($dateGroup['available_count']) }}</span>
                    <span class="badge text-bg-warning">رزرو/قفل: {{ \App\Support\PersianDate::number($dateGroup['reserved_count']) }}</span>
                </div>
            </div>
            <div class="slot-interval-list">
                @foreach($dateGroup['intervals'] as $interval)
                    <div class="slot-interval-row">
                        <div class="text-nowrap ltr">{{ $interval['label'] }}</div>
                        <div>{{ $interval['advisor_name'] ?: '-' }}</div>
                        <div>
                            <span class="badge {{ $interval['available'] ? 'text-bg-success' : 'text-bg-warning' }}">
                                {{ $interval['status_label'] }}
                            </span>
                        </div>
                        <div class="row-actions">
                            <a class="btn btn-outline-info" href="{{ route('admin.slots.show', $interval['slot_id']) }}" title="مشاهده">
                                <i class="ri-eye-line"></i>
                            </a>
                            @can('update_slots')
                                <a class="btn btn-outline-primary" href="{{ route('admin.slots.edit', $interval['slot_id']) }}" title="ویرایش">
                                    <i class="ri-edit-line"></i>
                                </a>
                            @endcan
                            @can('delete_slots')
                                <form method="post" action="{{ route('admin.slots.destroy', $interval['slot_id']) }}" onsubmit="return confirm('حذف شود؟')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-outline-danger" title="حذف">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <i class="ri-calendar-close-line"></i>
                تایمی یافت نشد
            </div>
        </div>
    @endforelse

    @if($slots->hasPages())
        <div class="card"><div class="pagination-wrap">{{ $slots->links() }}</div></div>
    @endif
@endsection

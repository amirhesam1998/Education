@extends('layouts.admin')

@section('title', 'داشبورد')
@section('subtitle', 'نمای کلی وضعیت سیستم')

@push('styles')
<style>
    /* ===========================================================
       Dashboard-only styles
    =========================================================== */
    .stat-grid{
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card{
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .stat-card:hover{
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon{
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .stat-icon.tone-brand  { background: var(--brand-100); color: var(--brand-700); }
    .stat-icon.tone-info   { background: #e5eefb; color: var(--info); }
    .stat-icon.tone-warn   { background: #fbf1de; color: var(--warning); }
    .stat-icon.tone-danger { background: #fbe6e4; color: var(--danger); }

    .stat-value{
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--ink-900);
        line-height: 1.3;
    }
    .stat-label{
        font-size: .8rem;
        color: var(--ink-500);
        margin-top: .15rem;
    }
    .stat-trend{
        font-size: .74rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .2rem;
        margin-top: .5rem;
    }
    .stat-trend.up{ color: var(--success); }
    .stat-trend.down{ color: var(--danger); }

    .panel-row{
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 1rem;
        align-items: start;
    }

    .empty-state{
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--ink-500);
    }
    .empty-state i{ font-size: 2.2rem; color: var(--ink-300); margin-bottom: .5rem; display: block; }

    .agenda-item{
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .85rem 0;
        border-bottom: 1px solid var(--border);
    }
    .agenda-item:last-child{ border-bottom: none; }

    .agenda-time{
        width: 58px;
        flex-shrink: 0;
        text-align: center;
        font-weight: 700;
        font-size: .82rem;
        color: var(--brand-700);
        background: var(--brand-50);
        border-radius: var(--radius-sm);
        padding: .4rem 0;
    }

    .agenda-title{ font-size: .87rem; font-weight: 600; color: var(--ink-900); }
    .agenda-sub{ font-size: .78rem; color: var(--ink-500); }

    @media (max-width: 1199.98px){
        .stat-grid{ grid-template-columns: repeat(2, 1fr); }
        .panel-row{ grid-template-columns: 1fr; }
    }
    @media (max-width: 575.98px){
        .stat-grid{ grid-template-columns: 1fr; }
        .stat-value{ font-size: 1.3rem; }
    }
</style>
@endpush

@section('content')

    {{-- Stat cards --}}
    <div class="stat-grid">
        <div class="stat-card">
            <div>
                <div class="stat-value">{{ $stats['today_reservations'] ?? 12 }}</div>
                <div class="stat-label">رزرو امروز</div>
                <div class="stat-trend up"><i class="ri-arrow-up-line"></i> ۸٪ نسبت به دیروز</div>
            </div>
            <div class="stat-icon tone-brand"><i class="ri-calendar-check-line"></i></div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-value">{{ $stats['pending_payments'] ?? 4 }}</div>
                <div class="stat-label">فیش در انتظار تایید</div>
                <div class="stat-trend down"><i class="ri-arrow-down-line"></i> ۲ مورد کمتر</div>
            </div>
            <div class="stat-icon tone-warn"><i class="ri-bank-card-line"></i></div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-value">{{ $stats['available_slots'] ?? 27 }}</div>
                <div class="stat-label">تایم خالی این هفته</div>
                <div class="stat-trend up"><i class="ri-arrow-up-line"></i> ۵ تایم جدید</div>
            </div>
            <div class="stat-icon tone-info"><i class="ri-time-line"></i></div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-value">{{ $stats['active_users'] ?? 138 }}</div>
                <div class="stat-label">کاربر فعال</div>
                <div class="stat-trend up"><i class="ri-arrow-up-line"></i> ۱۲ کاربر جدید</div>
            </div>
            <div class="stat-icon tone-danger"><i class="ri-user-heart-line"></i></div>
        </div>
    </div>

    <div class="panel-row">
        {{-- Recent reservations table --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ri-file-list-3-line align-middle text-muted me-1"></i> آخرین رزروها</span>
                <a href="{{ route('admin.reservations.index') }}" class="small">مشاهده همه</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>مراجع</th>
                            <th>مشاور</th>
                            <th>تاریخ و ساعت</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($reservations ?? []) as $r)
                            <tr>
                                <td>{{ $r->client_name }}</td>
                                <td>{{ $r->counselor_name }}</td>
                                <td class="ltr">{{ $r->scheduled_at }}</td>
                                <td><span class="badge bg-{{ $r->status_color }}">{{ $r->status_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td>سارا احمدی</td><td>دکتر رضایی</td><td class="">2025/07/12 10:30</td><td><span class="badge bg-success">تایید شده</span></td></tr>
                            <tr><td>محمد کریمی</td><td>دکتر موسوی</td><td class="">2025/07/12 12:00</td><td><span class="badge bg-warning">در انتظار</span></td></tr>
                            <tr><td>نیلوفر صادقی</td><td>دکتر رضایی</td><td class="">2025/07/13 09:00</td><td><span class="badge bg-info">جدید</span></td></tr>
                            <tr><td>امیر حسینی</td><td>دکتر جعفری</td><td class="">2025/07/13 16:30</td><td><span class="badge bg-danger">لغو شده</span></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Today's agenda --}}
        <div class="card">
            <div class="card-header">
                <i class="ri-calendar-2-line align-middle text-muted me-1"></i> برنامه امروز
            </div>
            <div class="p-3">
                @forelse(($agenda ?? []) as $item)
                    <div class="agenda-item">
                        <div class="agenda-time ltr">{{ $item->time }}</div>
                        <div>
                            <div class="agenda-title">{{ $item->title }}</div>
                            <div class="agenda-sub">{{ $item->subtitle }}</div>
                        </div>
                    </div>
                @empty
                    <div class="agenda-item">
                        <div class="agenda-time ltr">09:00</div>
                        <div><div class="agenda-title">جلسه مشاوره تحصیلی</div><div class="agenda-sub">نیلوفر صادقی — دکتر رضایی</div></div>
                    </div>
                    <div class="agenda-item">
                        <div class="agenda-time ltr">10:30</div>
                        <div><div class="agenda-title">جلسه مشاوره تحصیلی</div><div class="agenda-sub">سارا احمدی — دکتر رضایی</div></div>
                    </div>
                    <div class="agenda-item">
                        <div class="agenda-time ltr">16:30</div>
                        <div><div class="agenda-title">جلسه مشاوره شغلی</div><div class="agenda-sub">امیر حسینی — دکتر جعفری</div></div>
                    </div>
                @endforelse

                @if(isset($agenda) && count($agenda) === 0)
                    <div class="empty-state">
                        <i class="ri-calendar-line"></i>
                        برای امروز جلسهای ثبت نشده است
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
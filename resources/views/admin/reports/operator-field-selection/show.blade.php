@extends('layouts.admin')

@section('title', 'جزئیات فعالیت انتخاب رشته')
@section('subtitle', $operator->name)

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.operator-field-selection.index', $filters) }}">بازگشت</a>
@endsection

@section('content')
    <div class="card"><div class="card-header"><i class="ri-user-search-line align-middle text-muted me-1"></i> فعالیت‌های {{ $operator->name }}</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>نوع فعالیت</th><th>رزرو</th><th>دانش‌آموز</th><th>نوع کنکور</th><th>انتخاب رشته</th><th>تاریخ فعالیت</th></tr></thead><tbody>@forelse($activities as $activity)<tr><td><span class="badge bg-{{ $activity['action'] === 'field_selection_plan_created' ? 'success' : 'info' }}">{{ $activity['action_label'] }}</span></td><td>@if($activity['reservation_id'] && auth()->user()->can('view_reservations'))<a href="{{ route('admin.reservations.show', $activity['reservation_id']) }}">رزرو #{{ \App\Support\PersianDate::number($activity['reservation_id']) }}</a>@else رزرو #{{ \App\Support\PersianDate::number($activity['reservation_id']) }} @endif</td><td>{{ $activity['student_name'] }}</td><td>{{ $activity['exam_type_label'] }}</td><td>@if($activity['plan_id'] && auth()->user()->can('view_field_selection'))<a href="{{ route('admin.reservations.field-selection.show', ['reservation' => $activity['reservation_id'], 'plan' => $activity['plan_id']]) }}">انتخاب رشته #{{ \App\Support\PersianDate::number($activity['plan_id']) }}</a>@else انتخاب رشته #{{ \App\Support\PersianDate::number($activity['plan_id']) }} @endif</td><td>{{ \App\Support\PersianDate::dateTime($activity['created_at']) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">هنوز فعالیتی ثبت نشده است.</td></tr>@endforelse</tbody></table></div></div>
@endsection

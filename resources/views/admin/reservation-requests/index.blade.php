@extends('layouts.admin')

@section('title', 'درخواست‌های رزرو')
@section('subtitle', 'بررسی درخواست‌های ثبت‌شده توسط دانش‌آموزان')

@section('content')
    <div class="card filter-card mb-4"><div class="card-body">
        <form class="row g-3">
            <div class="col-md-3"><label class="form-label">نام دانش‌آموز</label><input name="full_name" value="{{ request('full_name') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">شماره تماس</label><input name="phone" value="{{ request('phone') }}" class="form-control" dir="ltr"></div>
            <div class="col-md-2"><label class="form-label">وضعیت</label><select name="status" class="form-select"><option value="">همه</option>@foreach(['pending' => 'در انتظار بررسی', 'approved' => 'تأیید شده', 'rejected' => 'رد شده', 'converted' => 'تبدیل شده به رزرو'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">از تاریخ</label><input name="from" value="{{ request('from') }}" class="form-control jalali-date-picker" autocomplete="off"></div>
            <div class="col-md-2"><label class="form-label">تا تاریخ</label><input name="to" value="{{ request('to') }}" class="form-control jalali-date-picker" autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label">نوع کنکور</label><select name="exam_type" class="form-select"><option value="">همه</option>@foreach($examTypes as $examType)<option value="{{ $examType }}" @selected(request('exam_type') === $examType)>{{ $examType }}</option>@endforeach</select></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary">اعمال فیلتر</button><a class="btn btn-outline-secondary" href="{{ route('admin.reservation-requests.index') }}">پاک کردن فیلترها</a></div>
        </form>
    </div></div>

    <div class="card"><div class="card-header"><i class="ri-user-add-line align-middle text-muted me-1"></i> درخواست‌های رزرو</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>دانش‌آموز</th><th>شماره تماس</th><th>رشته / کنکور</th><th>منطقه</th><th>سهمیه خاص</th><th>وضعیت</th><th>تاریخ ثبت</th><th></th></tr></thead><tbody>
        @forelse($requests as $reservationRequest)
            @php($statuses = ['pending' => ['در انتظار بررسی', 'warning'], 'approved' => ['تأیید شده', 'success'], 'rejected' => ['رد شده', 'danger'], 'converted' => ['تبدیل شده به رزرو', 'info']])
            <tr><td>{{ $reservationRequest->full_name }}</td><td dir="ltr">{{ $reservationRequest->phone_1 }}</td><td>{{ $reservationRequest->major ?: '-' }}<small class="d-block text-muted">{{ collect($reservationRequest->exam_type)->implode('، ') }}</small></td><td>{{ $reservationRequest->region ?: '-' }}</td><td>{{ $reservationRequest->special_quota ?: '-' }}</td><td><span class="badge bg-{{ $statuses[$reservationRequest->status][1] }}">{{ $statuses[$reservationRequest->status][0] }}</span></td><td>{{ \App\Support\PersianDate::dateTime($reservationRequest->created_at) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reservation-requests.show', $reservationRequest) }}">مشاهده</a></td></tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">درخواستی ثبت نشده است.</td></tr>
        @endforelse
    </tbody></table></div></div>
    <div class="mt-3">{{ $requests->links() }}</div>
@endsection

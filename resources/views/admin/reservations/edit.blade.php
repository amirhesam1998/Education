@extends('layouts.admin')

@section('title', 'ویرایش رزرو')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.reservations.update', $reservation) }}">
                @method('put')
                @include('admin.reservations._form', ['mode' => 'edit', 'defaultDeadlineHours' => 24, 'defaultPrepaymentAmount' => null])
            </form>
        </div>
    </div>
@endsection

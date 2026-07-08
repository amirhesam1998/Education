@extends('layouts.admin')

@section('title', 'رزرو جدید')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.reservations.store') }}">
                @include('admin.reservations._form', ['mode' => 'create'])
            </form>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'ایجاد تایم')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.slots.store') }}">
                @include('admin.slots._form', ['mode' => 'create'])
            </form>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'کاربر جدید')

@section('content')
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.users.store') }}">
            @include('admin.users._form', ['mode' => 'create'])
        </form>
    </div></div>
@endsection

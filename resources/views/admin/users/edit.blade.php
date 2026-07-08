@extends('layouts.admin')

@section('title', 'ویرایش کاربر')

@section('content')
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.users.update', $user) }}">
            @method('put')
            @include('admin.users._form', ['mode' => 'edit'])
        </form>
    </div></div>
@endsection

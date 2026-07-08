@extends('layouts.admin')

@section('title', 'ویرایش نقش')

@section('content')
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.roles.update', $role) }}">
            @method('put')
            @include('admin.roles._form')
        </form>
    </div></div>
@endsection

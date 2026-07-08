@extends('layouts.admin')

@section('title', 'نقش جدید')

@section('content')
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.roles.store') }}">
            @include('admin.roles._form')
        </form>
    </div></div>
@endsection

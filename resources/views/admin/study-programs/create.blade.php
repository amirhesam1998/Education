@extends('layouts.admin')

@section('title', 'افزودن رشته‌محل')

@section('content')
    <form method="post" action="{{ route('admin.study-programs.store') }}">
        @include('admin.study-programs._form')
    </form>
@endsection

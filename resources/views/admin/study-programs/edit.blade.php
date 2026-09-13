@extends('layouts.admin')

@section('title', 'ویرایش رشته‌محل')

@section('content')
    <form method="post" action="{{ route('admin.study-programs.update', $program) }}">
        @include('admin.study-programs._form')
    </form>
@endsection

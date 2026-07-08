@extends('layouts.admin')

@section('title', 'ویرایش تایم')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.slots.update', $slot) }}">
                @method('put')
                @include('admin.slots._form', ['mode' => 'edit'])
            </form>
        </div>
    </div>
@endsection

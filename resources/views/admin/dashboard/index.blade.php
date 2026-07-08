@extends('layouts.admin')

@section('title', 'داشبورد')

@section('content')
    <div class="row g-3">
        @foreach($cards as $label => $value)
            <div class="col-xl-3 col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-2">{{ $label }}</div>
                        <div class="fs-3 fw-bold">{{ \App\Support\PersianDate::number($value) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

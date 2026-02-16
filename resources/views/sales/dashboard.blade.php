@extends('layouts.master')

@section('content')
    <div class="container">

        <h2 class="mb-4">
            @if ($user->hasRole('Sales Manager'))
                Sales Team Dashboard
            @else
                My Dashboard
            @endif
        </h2>

        {{-- Main KPI row --}}
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Monthly Revenue</h6>
                        <h4>Rp {{ number_format($monthlyRevenue, 0) }}</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Conversion Rate</h6>
                        <h4>{{ $conversionRate }}%</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Monthly Target</h6>
                        <h4>Rp {{ number_format($targetAmount, 0) }}</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Achievement</h6>
                        <h4>{{ $achievement }}%</h4>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- List of inquiries that require follow-up --}}
        <div class="row">
            <div class="col-12">
                <h5>Pending Inquiries</h5>
                <div class="card">
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse ($pendingInquiries as $inquiry)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $inquiry->inquiry_number }} - <strong>{{ $inquiry->customer->name }}</strong></span>
                                    <span class="badge bg-primary rounded-pill">{{ $inquiry->status }}</span>
                                </li>
                            @empty
                                <li class="list-group-item">No pending inquiries. Great job!</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

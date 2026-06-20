@extends('larapay::layouts.payment')

@php $lang = $display ?? 'en'; @endphp

@section('title', $storeName . ' — Secure Payment')

@section('card-badge')
  <span class="badge bg-{{ $mode === 'test' ? 'warning' : 'success' }} text-uppercase">
    {{ $mode === 'test' ? 'Test Mode' : 'Live' }}
  </span>
@endsection

@section('card-title')
  <i class="ti ti-lock text-success me-1"></i>
  Secure Payment &mdash; {{ $storeName }}
@endsection

@section('content')

  {{-- Server-side error --}}
  @isset($error)
  <div class="alert alert-danger mb-3" role="alert">
    <div class="d-flex align-items-center">
      <i class="ti ti-alert-circle h4 me-2 mb-0"></i>
      <div><strong>Payment failed.</strong> {{ $error }}</div>
    </div>
  </div>
  @endisset

  {{-- Order summary --}}
  <div class="row g-2 mb-4">
    <div class="col-7">
      <div class="text-muted small text-uppercase fw-medium">Order</div>
      <div class="fw-semibold text-truncate">{{ $orderId }}</div>
      @if($description)
        <div class="text-muted small mt-1">{{ $description }}</div>
      @endif
    </div>
    <div class="col-5 text-end">
      <div class="text-muted small text-uppercase fw-medium">Amount</div>
      <div class="fs-3 fw-bold text-primary lh-1">{{ $amount }}</div>
      <div class="text-muted small">{{ $currency }}</div>
    </div>
  </div>

  <hr class="my-3">

  {{-- Pay button — redirects to Kashier's secure hosted session page --}}
  <div class="d-grid">
    <a href="{{ $sessionUrl }}" class="btn btn-primary btn-lg">
      <i class="ti ti-lock me-2"></i>
      Pay {{ $amount }} {{ $currency }} securely
    </a>
  </div>

  <p class="text-center text-muted mt-3 mb-0" style="font-size:.78rem;">
    <i class="ti ti-shield-check text-success me-1"></i>
    You will be redirected to Kashier's secure payment page. PCI DSS Level 1 certified.
  </p>

@endsection

@section('below-card')
  @if($mode === 'test')
  <div class="card mt-3 border-warning">
    <div class="card-body py-2 px-3">
      <p class="mb-1 text-warning fw-bold small"><i class="ti ti-flask me-1"></i>Test cards</p>
      <table class="table table-sm table-borderless mb-0 small">
        <tr><td>Success</td><td><code>5111 1111 1111 1118</code></td><td>06/27</td><td>100</td></tr>
        <tr><td>3-D Secure</td><td><code>5123 4500 0000 0008</code></td><td>06/27</td><td>100</td></tr>
        <tr><td>Failure</td><td><code>5111 1111 1111 1118</code></td><td>05/20</td><td>102</td></tr>
      </table>
    </div>
  </div>
  @endif
@endsection

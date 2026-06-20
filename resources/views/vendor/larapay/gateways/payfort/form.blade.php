@extends('larapay::layouts.payment')

@section('title', $storeName . ' — Redirecting to Secure Payment…')

@section('brand-icons')
  <span class="payment payment-xl payment-provider-visa me-1"></span>
  <span class="payment payment-xl payment-provider-mastercard me-1"></span>
  <span class="payment payment-xl payment-provider-amex"></span>
@endsection

@section('head')
  <style>
    .spinner-grow { width: 1rem; height: 1rem; }
  </style>
@endsection

@section('card-class', 'text-center')

@section('card-title')
  <i class="ti ti-arrow-right text-primary me-1"></i>
  Redirecting to Secure Payment
@endsection

@section('card-badge')
  <span class="badge bg-{{ $mode === 'sandbox' ? 'warning' : 'success' }} text-uppercase">
    {{ $mode === 'sandbox' ? 'Sandbox Mode' : 'Live' }}
  </span>
@endsection

@section('card-body-class', 'py-5')

@section('content')

  {{-- Animated loading dots --}}
  <span class="spinner-grow text-primary me-1" role="status"></span>
  <span class="spinner-grow text-primary me-1" role="status" style="animation-delay:.15s"></span>
  <span class="spinner-grow text-primary" role="status" style="animation-delay:.3s"></span>

  <p class="text-muted mt-3 mb-4">
    Please wait while we redirect you to the Amazon Payment Services page.
    <br>Do not close or refresh this page.
  </p>

  {{-- Order summary --}}
  <div class="row g-2 mb-4 text-start">
    <div class="col-6 text-muted small text-uppercase fw-medium">Order</div>
    <div class="col-6 fw-semibold text-end text-truncate">{{ $params['merchant_reference'] }}</div>
    <div class="col-6 text-muted small text-uppercase fw-medium">Amount</div>
    <div class="col-6 fw-bold text-primary text-end">{{ $amount }} {{ $currency }}</div>
  </div>

  {{-- Hidden APS form — auto-submitted by JS below --}}
  <form id="aps-form" method="POST" action="{{ $action }}">
    @foreach($params as $key => $value)
      <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach
    <noscript>
      <button type="submit" class="btn btn-primary mt-3">{{ $submitLabel }}</button>
    </noscript>
  </form>

  <p class="text-muted mt-3 mb-0" style="font-size:.78rem;">
    <i class="ti ti-shield-check text-success me-1"></i>
    Secured by Amazon Payment Services (Payfort) &mdash; PCI DSS Level 1
  </p>

@endsection

@section('scripts')
<script>
  window.addEventListener('load', function () {
    document.getElementById('aps-form').submit();
  });
</script>
@endsection

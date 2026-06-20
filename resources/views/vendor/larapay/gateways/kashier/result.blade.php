@extends('larapay::layouts.payment')

@section('title', ($storeName ?? 'Store') . ' — Payment ' . ucfirst($status))

@section('card-class', 'text-center')

@section('card-title')
  @if($status === 'success')
    <i class="ti ti-circle-check text-success me-1"></i> Payment Successful
  @elseif($status === 'failed')
    <i class="ti ti-circle-x text-danger me-1"></i> Payment Failed
  @else
    <i class="ti ti-clock-pause text-warning me-1"></i> Payment Pending
  @endif
@endsection

@section('card-badge')
  <span class="badge bg-{{ $status === 'success' ? 'success' : ($status === 'failed' ? 'danger' : 'warning') }} text-uppercase">
    {{ ucfirst($status) }}
  </span>
@endsection

@section('card-body-class', 'py-5')

@section('content')

  {{-- Status icon --}}
  @if($status === 'success')
    <span class="avatar avatar-xl bg-success-lt mb-4">
      <i class="ti ti-circle-check" style="font-size:2.5rem;color:#2fb344;"></i>
    </span>
    <h2 class="mb-1">Payment Successful</h2>
    <p class="text-muted mb-4">Your payment has been processed successfully.</p>

  @elseif($status === 'failed')
    <span class="avatar avatar-xl bg-danger-lt mb-4">
      <i class="ti ti-circle-x" style="font-size:2.5rem;color:#d63939;"></i>
    </span>
    <h2 class="mb-1">Payment Failed</h2>
    <p class="text-muted mb-4">Your card was declined. Please try a different card.</p>

  @else
    <span class="avatar avatar-xl bg-warning-lt mb-4">
      <i class="ti ti-clock-pause" style="font-size:2.5rem;color:#f76707;"></i>
    </span>
    <h2 class="mb-1">Payment Pending</h2>
    <p class="text-muted mb-4">Your payment is being processed. We'll update you shortly.</p>
  @endif

  <hr class="my-4">

  {{-- Transaction details --}}
  <div class="row g-2 text-start">
    <div class="col-6 text-muted small text-uppercase fw-medium">Transaction ID</div>
    <div class="col-6 fw-semibold text-end">{{ $transaction->refrance ?? '—' }}</div>

    <div class="col-6 text-muted small text-uppercase fw-medium">Amount</div>
    <div class="col-6 fw-semibold text-end">{{ $transaction->amount }} {{ $transaction->currency }}</div>

    <div class="col-6 text-muted small text-uppercase fw-medium">Gateway</div>
    <div class="col-6 fw-semibold text-end text-capitalize">{{ $transaction->gateway }}</div>

    <div class="col-6 text-muted small text-uppercase fw-medium">Status</div>
    <div class="col-6 fw-semibold text-end text-{{ $status === 'success' ? 'success' : ($status === 'failed' ? 'danger' : 'warning') }}">
      {{ ucfirst($transaction->status) }}
    </div>
  </div>

  <div class="mt-4">
    <a href="{{ url('/') }}" class="btn btn-outline-secondary">
      <i class="ti ti-home me-1"></i>Back to Home
    </a>
  </div>

@endsection

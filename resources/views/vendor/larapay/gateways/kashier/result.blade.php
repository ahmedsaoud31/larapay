<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $storeName ?? 'Test Store' }} &mdash; Payment {{ ucfirst($status) }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
  <style>body { background: #f4f6fb; }</style>
</head>
<body class="d-flex flex-column antialiased">
<div class="page page-center">
  <div class="container container-tight py-5">
    <div class="card card-md shadow-sm text-center">
      <div class="card-body py-5">

        @if($status === 'success')
          <span class="avatar avatar-xl bg-success-lt mb-3">
            <i class="ti ti-circle-check" style="font-size:2.5rem;color:#2fb344;"></i>
          </span>
          <h2 class="mb-1">Payment Successful</h2>
          <p class="text-muted">Your payment has been processed.</p>

        @elseif($status === 'failed')
          <span class="avatar avatar-xl bg-danger-lt mb-3">
            <i class="ti ti-circle-x" style="font-size:2.5rem;color:#d63939;"></i>
          </span>
          <h2 class="mb-1">Payment Failed</h2>
          <p class="text-muted">Your card was declined. Please try a different card.</p>

        @else
          <span class="avatar avatar-xl bg-warning-lt mb-3">
            <i class="ti ti-clock-pause" style="font-size:2.5rem;color:#f76707;"></i>
          </span>
          <h2 class="mb-1">Payment Pending</h2>
          <p class="text-muted">Your payment is being processed. We'll update you shortly.</p>
        @endif

        <hr class="my-4">

        <div class="row g-2 text-start">
          <div class="col-6 text-muted small text-uppercase fw-medium">Transaction ID</div>
          <div class="col-6 fw-semibold text-end">{{ $transaction->refrance ?? '—' }}</div>
          <div class="col-6 text-muted small text-uppercase fw-medium">Amount</div>
          <div class="col-6 fw-semibold text-end">{{ $transaction->amount }} {{ $transaction->currency }}</div>
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

      </div>
    </div>
  </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $storeName }} &mdash; Redirecting to Secure Payment…</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-payments.min.css">
  <style>
    body { background: #f4f6fb; }
    .spinner-grow { width: 1rem; height: 1rem; }
  </style>
</head>
<body class="d-flex flex-column antialiased">
<div class="page page-center">
  <div class="container container-tight py-5">

    <div class="card card-md shadow-sm text-center">
      <div class="card-body py-5">

        <div class="text-center mb-4">
          <span class="payment payment-xl payment-provider-visa me-1"></span>
          <span class="payment payment-xl payment-provider-mastercard me-1"></span>
          <span class="payment payment-xl payment-provider-amex"></span>
        </div>

        <span class="spinner-grow text-primary me-1" role="status"></span>
        <span class="spinner-grow text-primary me-1" role="status" style="animation-delay:.15s"></span>
        <span class="spinner-grow text-primary" role="status" style="animation-delay:.3s"></span>

        <h3 class="mt-3 mb-1">Redirecting to Secure Payment</h3>
        <p class="text-muted mb-4">
          Please wait while we redirect you to the Amazon Payment Services page.
          <br>Do not close or refresh this page.
        </p>

        <div class="row g-2 mb-4 text-start">
          <div class="col-6 text-muted small text-uppercase fw-medium">Order</div>
          <div class="col-6 fw-semibold text-end text-truncate">{{ $params['merchant_reference'] }}</div>
          <div class="col-6 text-muted small text-uppercase fw-medium">Amount</div>
          <div class="col-6 fw-bold text-primary text-end">{{ $amount }} {{ $currency }}</div>
        </div>

        <span class="badge bg-{{ $mode === 'sandbox' ? 'warning' : 'success' }} text-uppercase mb-3">
          {{ $mode === 'sandbox' ? 'Sandbox Mode' : 'Live' }}
        </span>

        {{-- The actual APS form — hidden, auto-submitted by JS --}}
        <form id="aps-form" method="POST" action="{{ $action }}">
          @foreach($params as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endforeach
          {{-- Fallback manual submit button (shown if JS is disabled) --}}
          <noscript>
            <button type="submit" class="btn btn-primary mt-3">{{ $submitLabel }}</button>
          </noscript>
        </form>

        <p class="text-muted mt-3 mb-0" style="font-size:.78rem;">
          <i class="ti ti-shield-check text-success me-1"></i>
          Secured by Amazon Payment Services (Payfort) &mdash; PCI DSS Level 1
        </p>

      </div>
    </div>

  </div>
</div>

<script>
  // Auto-submit as soon as the page loads
  window.addEventListener('load', function () {
    document.getElementById('aps-form').submit();
  });
</script>
</body>
</html>

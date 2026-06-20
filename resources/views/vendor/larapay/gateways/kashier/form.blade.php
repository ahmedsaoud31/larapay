<!DOCTYPE html>
<html lang="{{ $display === 'ar' ? 'ar' : 'en' }}" dir="{{ $display === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $storeName }} &mdash; Secure Payment</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-payments.min.css">
  <style>body { background: #f4f6fb; }</style>
</head>
<body class="d-flex flex-column antialiased">
<div class="page page-center">
  <div class="container container-tight py-5">

    {{-- Brand icons --}}
    <div class="text-center mb-4">
      <span class="payment payment-xl payment-provider-visa me-1"></span>
      <span class="payment payment-xl payment-provider-mastercard me-1"></span>
    </div>

    <div class="card card-md shadow-sm">
      <div class="card-header">
        <h3 class="card-title">
          <i class="ti ti-lock text-success me-1"></i>
          Secure Payment &mdash; {{ $storeName }}
        </h3>
        <div class="card-options">
          <span class="badge bg-{{ $mode === 'test' ? 'warning' : 'success' }} text-uppercase">
            {{ $mode === 'test' ? 'Test Mode' : 'Live' }}
          </span>
        </div>
      </div>

      <div class="card-body">

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

      </div>
    </div>

    {{-- Test cards hint --}}
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

  </div>
</div>
</body>
</html>

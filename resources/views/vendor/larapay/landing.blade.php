<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Larapay — Payment Gateway Tester</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-payments.min.css">
  <style>
    body { background: #f4f6fb; }
    .gateway-card { transition: box-shadow .18s, transform .18s; cursor: pointer; text-decoration: none !important; }
    .gateway-card:hover { box-shadow: 0 4px 24px rgba(0,0,0,.12) !important; transform: translateY(-2px); }
    .gateway-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.6rem; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .mode-badge { font-size: .68rem; letter-spacing: .04em; }
  </style>
</head>
<body class="antialiased">

<div class="page">
  <div class="container-xl py-5">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="page-header mb-4">
      <div class="row align-items-center">
        <div class="col-auto">
          <div class="page-pretitle text-muted">Larapay {{ $version }}</div>
          <h2 class="page-title">
            <i class="ti ti-credit-card me-2 text-primary"></i>
            Payment Gateway Tester
          </h2>
        </div>
        <div class="col-auto ms-auto">
          <span class="badge bg-{{ $mode === 'sandbox' ? 'warning' : 'success' }} mode-badge text-uppercase px-3 py-2">
            <i class="ti ti-{{ $mode === 'sandbox' ? 'flask' : 'rocket' }} me-1"></i>
            {{ ucfirst($mode) }} Mode
          </span>
        </div>
      </div>
    </div>

    {{-- ── Global info bar ─────────────────────────────────────────────── --}}
    <div class="row mb-4 g-3">
      <div class="col-sm-4">
        <div class="card">
          <div class="card-body py-3 d-flex align-items-center gap-3">
            <span class="bg-blue-lt rounded p-2"><i class="ti ti-world text-blue fs-3"></i></span>
            <div>
              <div class="text-muted small">Default Currency</div>
              <div class="fw-bold">{{ $currency }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="card">
          <div class="card-body py-3 d-flex align-items-center gap-3">
            <span class="bg-green-lt rounded p-2"><i class="ti ti-plug text-green fs-3"></i></span>
            <div>
              <div class="text-muted small">Active Gateways</div>
              <div class="fw-bold">{{ count($gateways) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="card">
          <div class="card-body py-3 d-flex align-items-center gap-3">
            <span class="bg-orange-lt rounded p-2"><i class="ti ti-settings text-orange fs-3"></i></span>
            <div>
              <div class="text-muted small">Default Gateway</div>
              <div class="fw-bold text-capitalize">{{ config('larapay.gateway') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ── Gateway cards ────────────────────────────────────────────────── --}}
    <div class="row g-4">

      @foreach($gateways as $gw)
      <div class="col-sm-6 col-lg-4">
        <div class="card shadow-sm h-100">

          <div class="card-header">
            <div class="d-flex align-items-center gap-3 w-100">
              <div class="gateway-icon bg-{{ $gw['color'] }}-lt">
                <i class="ti {{ $gw['icon'] }} text-{{ $gw['color'] }}"></i>
              </div>
              <div class="flex-fill">
                <div class="fw-bold fs-4">{{ $gw['label'] }}</div>
                <div class="text-muted small">{{ $gw['region'] }}</div>
              </div>
              <span class="status-dot bg-{{ $gw['configured'] ? 'success' : 'danger' }}"
                    title="{{ $gw['configured'] ? 'Configured' : 'Not configured' }}">
              </span>
            </div>
          </div>

          <div class="card-body d-flex flex-column gap-3">

            {{-- Description --}}
            <p class="text-muted small mb-0">{{ $gw['description'] }}</p>

            {{-- Currency badge --}}
            <div class="d-flex gap-2 flex-wrap">
              @foreach($gw['currencies'] as $cur)
                <span class="badge bg-blue-lt text-blue border border-blue-subtle">{{ $cur }}</span>
              @endforeach
              @foreach($gw['methods'] as $method)
                <span class="badge bg-muted-lt text-muted border">{{ $method }}</span>
              @endforeach
            </div>

            {{-- Credentials check --}}
            @if(!$gw['configured'])
            <div class="alert alert-warning alert-sm py-2 mb-0 small">
              <i class="ti ti-alert-triangle me-1"></i>
              Missing credentials — check <code>.env</code>
            </div>
            @endif

          </div>

          <div class="card-footer d-flex flex-column gap-2">
            @foreach($gw['actions'] as $action)
            <a
              href="{{ $action['url'] }}"
              class="btn btn-{{ $action['style'] }} w-100 {{ !$gw['configured'] ? 'disabled' : '' }}"
              @if(!$gw['configured']) tabindex="-1" aria-disabled="true" @endif
            >
              <i class="ti {{ $action['icon'] }} me-1"></i>
              {{ $action['label'] }}
            </a>
            @endforeach
          </div>

        </div>
      </div>
      @endforeach

    </div>{{-- /row --}}

    {{-- ── Callbacks reference ─────────────────────────────────────────── --}}
    <div class="card mt-5">
      <div class="card-header">
        <h3 class="card-title"><i class="ti ti-webhook me-2 text-purple"></i>Callback Routes</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr class="text-muted text-uppercase small">
                <th>Method</th>
                <th>Path</th>
                <th>Name</th>
                <th>Purpose</th>
              </tr>
            </thead>
            <tbody>
              @foreach($gateways as $gw)
              <tr>
                <td><span class="badge bg-green-lt text-green">ANY</span></td>
                <td><code>/larapay/{{ $gw['key'] }}/client-callback</code></td>
                <td class="text-muted">larapay.client-callback</td>
                <td class="text-muted small">Customer redirect after payment</td>
              </tr>
              <tr>
                <td><span class="badge bg-blue-lt text-blue">ANY</span></td>
                <td><code>/larapay/{{ $gw['key'] }}/server-callback</code></td>
                <td class="text-muted">larapay.server-callback</td>
                <td class="text-muted small">Webhook / server notification</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ── Footer ──────────────────────────────────────────────────────── --}}
    <div class="text-center text-muted mt-5 small">
      <i class="ti ti-lock me-1"></i>
      Larapay {{ $version }} &mdash; For testing purposes only. Never use real card data in sandbox mode.
    </div>

  </div>{{-- /container --}}
</div>{{-- /page --}}

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>

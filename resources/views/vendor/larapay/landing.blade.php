<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>{{ __('larapay::larapay.payment_gateway_tester') }} — Larapay</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css"/>
  @if(app()->getLocale() === 'ar')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-rtl.min.css"/>
  @endif
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-payments.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
  <style>
    body{background:#f4f6fb}
    .gateway-icon{width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:10px;font-size:1.4rem;flex-shrink:0}
    .status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}
    .badge-status-success{background:#d1f5dc;color:#1a7a36}
    .badge-status-pending{background:#fff3cd;color:#856404}
    .badge-status-cancelled{background:#f8d7da;color:#842029}
    .badge-status-clickable{cursor:pointer;text-decoration:none;border:none;transition:opacity .15s}
    .badge-status-clickable:hover{opacity:.75}
    .section-title{font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6c757d}
  </style>
</head>
<body>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">Larapay {{ $version }}</div>
        <h2 class="page-title"><i class="ti ti-credit-card me-2 text-primary"></i>{{ __('larapay::larapay.payment_gateway_tester') }}</h2>
      </div>
      <div class="col-auto ms-auto d-flex align-items-center gap-2">
        {{-- Language switcher --}}
        <a href="{{ route('larapay.lang', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
           class="btn btn-sm btn-outline-secondary">
          <i class="ti ti-language me-1"></i>{{ __('larapay::larapay.lang_switch') }}
        </a>
        <span class="badge bg-{{ $mode==='sandbox'?'warning':'success' }} text-{{ $mode==='sandbox'?'dark':'' }} fs-6 px-3 py-2">
          <i class="ti ti-{{ $mode==='sandbox'?'flask':'shield-check' }} me-1"></i>
          {{ $mode==='sandbox' ? __('larapay::larapay.sandbox_mode') : __('larapay::larapay.live_mode') }}
        </span>
      </div>
    </div>
  </div>
</div>
<div class="page-body">
  <div class="container-xl">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-3">
      <i class="ti ti-circle-check me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-3">
      <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
      @foreach([
        ['bg-blue-lt','ti-building-bank', __('larapay::larapay.gateways'),        count($gateways)],
        ['bg-green-lt','ti-receipt',      __('larapay::larapay.transactions'),     $transactions->total()],
        ['bg-yellow-lt','ti-currency-dollar', __('larapay::larapay.default_currency'), $currency],
        ['bg-teal-lt','ti-circle-check',  __('larapay::larapay.successful'),       $successfulTransactionsCount],
      ] as [$bg,$icon,$label,$val])
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar {{ $bg }} me-3"><i class="ti {{ $icon }}" style="font-size:1.3rem"></i></div>
              <div class="subheader">{{ $label }}</div>
            </div>
            <div class="h1 mb-0">{{ $val }}</div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- Test form --}}
    <form id="testPayForm" method="POST" action="{{ route('larapay.test-pay') }}">
      @csrf
      <input type="hidden" name="gateway" id="fieldGateway">
      <input type="hidden" name="action"  id="fieldAction">

      <div class="row g-4 mb-4">
        {{-- Billing --}}
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-user me-2"></i>{{ __('larapay::larapay.billing_details') }}</h3></div>
            <div class="card-body">
              <div class="mb-3">
                <label class="section-title mb-1">{{ __('larapay::larapay.full_name') }} *</label>
                <input type="text" class="form-control" name="billing_name" value="Ahmed Aboelsaoud" required>
              </div>
              <div class="mb-3">
                <label class="section-title mb-1">{{ __('larapay::larapay.email') }} *</label>
                <input type="email" class="form-control" name="billing_email" value="test@example.com" required>
              </div>
              <div class="mb-3">
                <label class="section-title mb-1">{{ __('larapay::larapay.phone') }}</label>
                <input type="text" class="form-control" name="billing_phone" value="+201000000000">
              </div>
              <div class="alert alert-info mb-0 small">
                <i class="ti ti-info-circle me-1"></i>{{ __('larapay::larapay.order_form_hint') }}
              </div>
            </div>
          </div>
        </div>

        {{-- Order --}}
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-shopping-cart me-2"></i>{{ __('larapay::larapay.order_details') }}</h3></div>
            <div class="card-body">
              <div class="mb-3">
                <label class="section-title mb-1">{{ __('larapay::larapay.amount') }} *</label>
                <div class="input-group">
                  <input type="number" class="form-control" name="cart_amount" id="cartAmount" value="100.00" min="0.01" step="0.01" required>
                  <span class="input-group-text fw-bold" id="currencyDisplay">EGP</span>
                </div>
              </div>
              <div class="mb-3">
                <label class="section-title mb-1">{{ __('larapay::larapay.currency') }} *</label>
                <input type="text" class="form-control text-uppercase" name="cart_currency" id="cartCurrency" value="EGP" maxlength="3" required>
              </div>
              <div class="mb-3">
                <label class="section-title mb-1">{{ __('larapay::larapay.description') }} *</label>
                <input type="text" class="form-control" name="cart_description" value="Test order from Larapay" maxlength="150" required>
              </div>
              <div class="alert alert-info mb-0 small">
                <i class="ti ti-info-circle me-1"></i>{{ __('larapay::larapay.order_form_hint') }}
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Gateway cards --}}
      <div class="row g-3 mb-4">
        @foreach($gateways as $gw)
        <div class="col-sm-6 col-lg-3">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-3">
              <div class="gateway-icon bg-{{ $gw['color'] }}-lt text-{{ $gw['color'] }}">
                <i class="ti {{ $gw['icon'] }}"></i>
              </div>
              <div class="flex-fill overflow-hidden">
                <div class="fw-bold text-truncate">{{ $gw['label'] }}</div>
                <div class="text-muted small">{{ $gw['region'] }}</div>
              </div>
              <span class="status-dot bg-{{ $gw['configured']?'success':'danger' }}"
                    title="{{ $gw['configured']?'Configured':'Not configured' }}"></span>
            </div>
            <div class="card-body">
              <p class="text-muted small mb-2">{{ $gw['description'] }}</p>
              <div class="d-flex flex-wrap gap-1 mb-2">
                @foreach($gw['currencies'] as $c)<span class="badge bg-blue-lt text-blue">{{ $c }}</span>@endforeach
                @foreach($gw['methods'] as $m)<span class="badge bg-muted-lt text-muted">{{ $m }}</span>@endforeach
              </div>
              @if(!$gw['configured'])
              <div class="alert alert-warning py-1 px-2 mb-0 small">
                <i class="ti ti-alert-triangle me-1"></i>{{ __('larapay::larapay.missing_credentials') }}
              </div>
              @endif
            </div>
            <div class="card-footer d-flex flex-column gap-2">
              @if($gw['configured'])
                @foreach($gw['actions'] as $act)
                <button type="button" class="btn btn-{{ $act['style'] }} btn-sm w-100"
                        onclick="submitGateway('{{ $gw['key'] }}','{{ $act['action'] }}')">
                  <i class="ti {{ $act['icon'] }} me-1"></i>{{ $act['label'] }}
                </button>
                @endforeach
              @else
                @foreach($gw['actions'] as $act)
                <span class="btn btn-{{ $act['style'] }} btn-sm w-100 disabled">
                  <i class="ti {{ $act['icon'] }} me-1"></i>{{ $act['label'] }}
                </span>
                @endforeach
              @endif
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </form>

    @include('larapay::landing.transactions')
    
    @include('larapay::landing.callback-routes')

    <div class="text-center text-muted small py-3">
      <i class="ti ti-lock me-1"></i>Larapay {{ $version }} — {{ __('larapay::larapay.for_dev_only') }}
    </div>

  </div>
</div>

<!-- include modals -->
@include('larapay::landing.modals.check-status')
@include('larapay::landing.modals.refund')
@include('larapay::landing.modals.response-viewer')

{{-- Hidden triggers — Bootstrap handles modal open via data-bs-* so we never call bootstrap.Modal directly --}}
<button type="button" id="checkTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#checkModal"></button>
<button type="button" id="refundTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#refundModal"></button>
<button type="button" id="responseTrigger" class="d-none" data-bs-toggle="modal" data-bs-target="#responseModal"></button>

{{-- Bootstrap config for larapay-landing.js — Blade values passed safely --}}
<script>
window.LarapayBootstrap = {
  csrf:      '{{ csrf_token() }}',
  checkUrl:  '{{ route("larapay.check-action") }}',
  refundUrl: '{{ route("larapay.refund-action") }}',
  locale:    '{{ app()->getLocale() }}',
  i18n: {
    success:         '{{ __("larapay::larapay.status_success") }}',
    pending:         '{{ __("larapay::larapay.status_pending") }}',
    cancelled:       '{{ __("larapay::larapay.status_cancelled") }}',
    db_updated:      '{{ __("larapay::larapay.db_updated") }}',
    unknown_error:   '{{ __("larapay::larapay.unknown_error") }}',
    network_error:   '{{ __("larapay::larapay.network_error") }}',
    no_data:         '{{ __("larapay::larapay.no_data") }}',
    processing:      '{{ __("larapay::larapay.processing") }}',
    close:           '{{ __("larapay::larapay.close") }}',
  }
};
// submitGateway must be global before gateway buttons are clicked
window.submitGateway = function(gateway, action) {
  document.getElementById('fieldGateway').value = gateway;
  document.getElementById('fieldAction').value  = action || 'default';
  setTimeout(function(){ document.getElementById('testPayForm').submit(); }, 20);
};
</script>

<script src="{{ asset('vendor/larapay/js/larapay-landing.js') }}"></script>

@yield('scripts')
</body>
</html>

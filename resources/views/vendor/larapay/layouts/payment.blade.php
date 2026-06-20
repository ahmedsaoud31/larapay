<!DOCTYPE html>
<html
  lang="{{ $lang ?? 'en' }}"
  dir="{{ ($lang ?? 'en') === 'ar' ? 'rtl' : 'ltr' }}"
>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', ($storeName ?? 'Larapay') . ' — Secure Payment')</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-payments.min.css">

  <style>
    body { background: #f4f6fb; }
  </style>

  @yield('head')
</head>

<body class="d-flex flex-column antialiased">
<div class="page page-center">
  <div class="container container-tight py-5">

    {{-- ── Brand icons ───────────────────────────────────────────────── --}}
    <div class="text-center mb-4">
      @section('brand-icons')
        <span class="payment payment-xl payment-provider-visa me-1"></span>
        <span class="payment payment-xl payment-provider-mastercard me-1"></span>
      @show
    </div>

    {{-- ── Main card ────────────────────────────────────────────────── --}}
    <div class="card card-md shadow-sm @yield('card-class')">

      {{-- Card header --}}
      <div class="card-header">
        <h3 class="card-title">
          @yield('card-title', '<i class="ti ti-lock text-success me-1"></i> Secure Payment')
        </h3>
        <div class="card-options">
          @yield('card-badge')
        </div>
      </div>

      {{-- Card body --}}
      <div class="card-body @yield('card-body-class')">
        @yield('content')
      </div>

    </div>{{-- /card --}}

    {{-- ── Below-card extras (test cards, notices, etc.) ───────────── --}}
    @yield('below-card')

  </div>{{-- /container --}}
</div>{{-- /page --}}

@yield('scripts')
</body>
</html>

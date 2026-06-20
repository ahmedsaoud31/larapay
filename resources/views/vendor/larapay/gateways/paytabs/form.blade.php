@extends('larapay::layouts.payment')

@section('title', 'Larapay — Secure Payment')

@section('brand-icons')
  <span class="payment payment-xl payment-provider-verisign me-1"></span>
  <span class="payment payment-xl payment-provider-visa me-1"></span>
  <span class="payment payment-xl payment-provider-mastercard me-1"></span>
  <span class="payment payment-xl payment-provider-jcb"></span>
@endsection

@section('card-title')
  <i class="ti ti-credit-card text-primary me-1"></i>
  Pay with Card
@endsection

@section('content')

  {{-- JS-populated error box --}}
  <div id="paymentErrors" class="alert alert-danger d-none mb-3" role="alert">
    <div class="d-flex">
      <i class="ti ti-alert-circle h4 me-2 mb-0"></i>
      <span id="paymentErrorText"></span>
    </div>
  </div>

  {{-- Card form — tokenised by PayTabs paylib.js --}}
  <form id="paytabsForm" method="post" action="" autocomplete="off" novalidate>

    <div class="mb-3">
      <label class="form-label">Card Holder Name</label>
      <div class="input-icon">
        <input class="form-control" placeholder="eg. Ahmed Aboelsaoud">
        <span class="input-icon-addon"><i class="ti ti-user-dollar"></i></span>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Card Number</label>
      <div class="input-icon">
        <input class="form-control" type="text" data-paylib="number"
               placeholder="4000 0000 0000 0002">
        <span class="input-icon-addon payment-icon">
          <span class="payment payment-provider-visa payment-xs me-1"></span>
          <span class="payment payment-provider-mastercard payment-xs"></span>
        </span>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-4">
        <label class="form-label">Month</label>
        <div class="input-group">
          <span class="input-group-text">MM</span>
          <input type="text" class="form-control" data-paylib="expmonth" placeholder="12">
        </div>
      </div>
      <div class="col-4">
        <label class="form-label">Year</label>
        <div class="input-group">
          <span class="input-group-text">YY</span>
          <input type="text" class="form-control" data-paylib="expyear" placeholder="27">
        </div>
      </div>
      <div class="col-4">
        <label class="form-label">CVV</label>
        <div class="input-group">
          <span class="input-group-text"><i class="ti ti-credit-card-refund"></i></span>
          <input type="text" class="form-control" data-paylib="cvv"
                 placeholder="123" autocomplete="off">
        </div>
      </div>
    </div>

    <div class="form-footer">
      <button type="submit" class="btn btn-primary w-100">
        <i class="ti ti-lock me-1"></i> Pay
      </button>
    </div>

  </form>

  <p class="text-center text-muted mt-3 mb-0" style="font-size:.78rem;">
    <i class="ti ti-shield-check text-success me-1"></i>
    Secured by PayTabs &mdash; PCI DSS Level 1
  </p>

@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
<script src="{{ config('larapay.paytabs.endpoint', 'https://secure-egypt.paytabs.com/') }}payment/js/paylib.js"></script>
<script>
  var myform = document.getElementById('paytabsForm');
  paylib.inlineForm({
    'key': '{{ $clientKey }}',
    'form': myform,
    'autoSubmit': true,
    'callback': function (response) {
      if (response.error) {
        var box = document.getElementById('paymentErrors');
        box.classList.remove('d-none');
        paylib.handleError(document.getElementById('paymentErrorText'), response);
      }
    }
  });
</script>
@endsection

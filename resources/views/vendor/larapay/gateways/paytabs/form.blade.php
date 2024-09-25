<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Larapay</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler-payments.min.css">
</head>

<body class=" d-flex flex-column">
  <div class="page page-center">
    <div class="container container-tight py-4">
      <div class="text-center mb-4">
        <span class="payment payment-xl payment-provider-verisign"></span>
      </div>
      <form id="paytabsForm" method="post" class="card card-md" action="" autocomplete="off" novalidate="">
        <div class="card-body">
          <div class="row">
            <div class="col col-sm-12 text-center">
              <span class="payment payment-md payment-provider-visa"></span>
              <span class="payment payment-md payment-provider-mastercard"></span>
              <span class="payment payment-md payment-provider-jcb"></span>
              <hr>
            </div>
          </div>
          <div class="row">
            <div class="col col-md-12">
              <div id="paymentErrors" class="alert alert-danger" role="alert" style="display: none;">
                <div class="d-flex">
                  <div>
                    <i class="ti ti-alert-circle h4"></i>
                  </div>
                  <span id="paymentErrorText" style="padding-left: 5px;"></span>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col col-sm-12">
              <label class="form-label">Card Holder Name</label>
              <div class="input-icon mb-2">
                <input class="form-control " placeholder="eg. Ahmed Aboelsaoud">
                <span class="input-icon-addon">
                  <i class="ti ti-user-dollar"></i>
                </span>
              </div>
            </div>
            <div class="col col-sm-12">
              <label class="form-label">Card number</label>
              <div class="input-icon mb-2">
                <input class="form-control " type="text" data-paylib="number"
                  placeholder="4000 0000 0000 0002" value="4000 0000 0000 0002">
                <span class="input-icon-addon payment-icon">
                  <span class="payment payment-provider-visa payment-xs me-2"></span>
                  <span class="payment payment-provider-mastercard payment-xs me-2"></span>
                </span>
              </div>
            </div>
            <div class="col col-sm-4">
              <label class="form-label">Expiry Month</label>
              <div class="input-group mb-2">
                <span class="input-group-text">MM</span>
                <input type="text" class="form-control" data-paylib="expmonth" placeholder="12" value="12">
              </div>
            </div>
            <div class="col col-sm-4">
              <label class="form-label">Expiry Year</label>
              <div class="input-group mb-2">
                <span class="input-group-text">YY</span>
                <input type="text" class="form-control" data-paylib="expyear" placeholder="27" value="27">
              </div>
            </div>
            <div class="col col-sm-4">
              <label class="form-label">CVV</label>
              <div class="input-group mb-2">
                <span class="input-group-text">
                  <i class="ti ti-credit-card-refund"></i>
                </span>
                <input type="text" class="form-control" data-paylib="cvv" placeholder="123" value="123"
                  autocomplete="off">
              </div>
            </div>
          </div>
          <div class="form-footer">
            <button type="submit" class="btn btn-primary col col-sm-12">Pay</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

  <script src="https://secure-egypt.paytabs.com/payment/js/paylib.js"></script>
  <script type="text/javascript">
  var myform = document.getElementById('paytabsForm');
  paylib.inlineForm({
    'key': '{{ $clientKey }}',
    'form': myform,
    'autoSubmit': true,
    'callback': function(response) {
      if (response.error) {
        document.getElementById('paymentErrors').style.display = 'block'
        paylib.handleError(document.getElementById('paymentErrorText'), response);
      }
    }
  })
  </script>
</body>

</html>
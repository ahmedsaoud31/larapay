<script src="https://secure-egypt.paytabs.com/payment/js/paylib.js"></script>

<form action="{{ route('larapay.pay') }}" id="payform" method="post">
  <span id="paymentErrors"></span>
  <div class="row">
    <label>Card Number</label>
    <input type="text" data-paylib="number" size="20" value="4000000000000002">
  </div>
  <div class="row">
    <label>Expiry Date (MM/YYYY)</label>
    <input type="text" data-paylib="expmonth" size="2" value="05">
    <input type="text" data-paylib="expyear" size="4" value="27">
  </div>
  <div class="row">
    <label>Security Code</label>
    <input type="text" data-paylib="cvv" size="4" value="123">
  </div>
  <input type="submit" value="Place order">
</form>
  
<script type="text/javascript">
var myform = document.getElementById('payform');
paylib.inlineForm({
  'key': '{{ $clientKey }}',
  'form': myform,
  'autoSubmit': true,
  'callback': function(response) {
    document.getElementById('paymentErrors').innerHTML = '';
    if (response.error) {             
      paylib.handleError(document.getElementById('paymentErrors'), response); 
    }
  }
});
</script>
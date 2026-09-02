<?php

namespace Larapay\Controllers;

use Larapay;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Larapay\Models\LarapayTransaction;

class LarapayController extends Controller
{

  public function __construct()
  {
    //
  }

    /**
     * Switch landing page language and redirect back.
     * GET /larapay/lang/{locale}
     */
    public function setLocale(string $locale)
    {
      $allowed = ['en', 'ar'];
      if (in_array($locale, $allowed)) {
        session(['larapay_locale' => $locale]);
      }
      return redirect()->route('larapay.landing');
    }

  public function run()
  {
    return redirect((request()->url));
  }

  /**
   * Landing page — shows all configured gateways with test links.
   * GET /larapay
   */
  public function landing()
  {
    // Apply package-level locale (separate from app locale)
    $locale = session('larapay_locale', config('app.locale', 'en'));
    if (in_array($locale, ['en', 'ar'])) {
      app()->setLocale($locale);
    }

    $mode     = config('larapay.mode', 'sandbox');
    $currency = config('larapay.currency', 'EGP');

    $gateways = [
      [
        'key'         => 'paytabs',
        'label'       => 'PayTabs',
        'region'      => 'MENA',
        'icon'        => 'ti-credit-card',
        'color'       => 'blue',
        'description' => 'Hosted card form with inline tokenisation. Supports Visa, Mastercard, AMEX and local methods.',
        'currencies'  => ['EGP', 'SAR', 'AED', 'USD'],
        'methods'     => ['Card', 'Hosted Form'],
        'configured'  => !empty(config("larapay.paytabs.{$mode}.server_key"))
                      && !empty(config('larapay.paytabs.profile_id')),
        'refundable'  => true,
        'actions' => [
          ['label' => 'Test Hosted Form',  'url' => route('larapay.form'),  'icon' => 'ti-window',      'style' => 'primary',          'currency' => 'EGP', 'action' => 'form'],
          ['label' => 'Test Redirect Pay', 'url' => route('larapay.test'),  'icon' => 'ti-arrow-right', 'style' => 'outline-secondary', 'currency' => 'EGP', 'action' => 'redirect'],
        ],
      ],
      [
        'key'         => 'paymob',
        'label'       => 'PayMob',
        'region'      => 'Egypt / MENA',
        'icon'        => 'ti-device-mobile',
        'color'       => 'green',
        'description' => 'Unified hosted checkout supporting card, wallet, and bank installments.',
        'currencies'  => ['EGP'],
        'methods'     => ['Card', 'Wallet', 'Installments'],
        'configured'  => !empty(config("larapay.paymob.{$mode}.api_key")),
        'refundable'  => false,
        'actions' => [
          ['label' => 'Test Checkout', 'url' => route('larapay.paymob'), 'icon' => 'ti-arrow-right', 'style' => 'primary', 'currency' => 'EGP', 'action' => 'default'],
        ],
      ],
      [
        'key'         => 'kashier',
        'label'       => 'Kashier',
        'region'      => 'Egypt',
        'icon'        => 'ti-building-bank',
        'color'       => 'orange',
        'description' => 'Two modes: HPP redirect with HMAC signature, or server-side session API with hosted payment page.',
        'currencies'  => ['EGP', 'USD', 'GBP', 'EUR'],
        'methods'     => ['Card', 'Wallet', 'Installments'],
        'configured'  => !empty(config("larapay.kashier.{$mode}.api_key"))
                      && !empty(config('larapay.kashier.mid')),
        'refundable'  => false,
        'actions' => [
          ['label' => 'HPP Redirect',     'url' => route('larapay.kashier'),      'icon' => 'ti-arrow-right', 'style' => 'primary',          'currency' => 'EGP', 'action' => 'default'],
          ['label' => 'Session API Form', 'url' => route('larapay.kashier-form'), 'icon' => 'ti-window',      'style' => 'outline-secondary', 'currency' => 'EGP', 'action' => 'session'],
        ],
      ],
      [
        'key'         => 'payfort',
        'label'       => 'Payfort (APS)',
        'region'      => 'MENA',
        'icon'        => 'ti-brand-amazon',
        'color'       => 'yellow',
        'description' => 'Amazon Payment Services hosted checkout. Auto-submitting form with SHA-256 signature.',
        'currencies'  => ['AED', 'SAR', 'EGP', 'USD', 'KWD'],
        'methods'     => ['Card', 'MADA', 'KNET', 'NAPS'],
        'configured'  => !empty(config("larapay.payfort.{$mode}.access_code"))
                      && !empty(config("larapay.payfort.{$mode}.merchant_identifier")),
        'refundable'  => true,
        'actions' => [
          ['label' => 'Test Hosted Checkout', 'url' => route('larapay.payfort'), 'icon' => 'ti-arrow-right', 'style' => 'primary', 'currency' => 'AED', 'action' => 'default'],
        ],
      ],
    ];

    $transactions = LarapayTransaction::with('children')
      ->withSum('children as refunded_total', 'amount')
      /*->whereNull('parent_id')*/
      ->orderBy('id', 'desc')
      ->paginate(15);

    return view('larapay::landing', [
      'gateways'     => $gateways,
      'mode'         => $mode,
      'currency'     => $currency,
      'version'      => 'v1.0',
      'transactions' => $transactions,
      'successfulTransactionsCount' => LarapayTransaction::where('status','success')->count(),
    ]);
  }

  /**
   * Process a refund from the landing page.
   * POST /larapay/refund-action  → returns JSON
   */
  public function refundAction(Request $request)
  {
    $request->validate([
      'transaction_id' => 'required|integer|exists:larapay_transactions,id',
      'amount'         => 'required|numeric|min:0.01',
    ]);

    $transaction = LarapayTransaction::findOrFail($request->transaction_id);

    // Gateways that support refund
    $refundable = ['paytabs', 'payfort', 'kashier'];
    if (! in_array($transaction->gateway, $refundable)) {
      return response()->json([
        'success' => false,
        'error'   => ucfirst($transaction->gateway) . ' does not support refunds via API.',
      ]);
    }

    try {
      $response = json_decode($transaction->response ?? '{}');
      $larapay  = Larapay::init(gateway: $transaction->gateway);

      switch ($transaction->gateway) {
        case 'paytabs':
          $refund = $larapay
            ->set(refrance: $transaction->refrance)
            ->cart(
              id:          $response->cart_id          ?? $transaction->uid,
              description: $response->cart_description ?? 'Refund for ' . $transaction->uid,
              amount:      (float) $request->amount,
            )
            ->refund((float) $request->amount);
          break;

        case 'payfort':
          $refund = $larapay
            ->set(
              uid:      $transaction->uid,
              refrance: $transaction->refrance,
              currency: $transaction->currency,
              amount:   (float) $request->amount,
            )
            ->refund((float) $request->amount);
          break;

        case 'kashier':
          // Kashier refund uses the uid (orderId originally sent to Kashier)
          $refund = $larapay
            ->set(
              amount: (float) $request->amount,
              transaction: $transaction,
              reason: 'Customer refund request'
            )
            ->refund();
          break;
      }

    } catch (\Throwable $e) {
      return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }

    if ($refund->hasError()) {
      return response()->json([
        'success' => false,
        'error'   => 'Refund failed: ' . $refund->getError(),
        'raw'     => $refund->json(),
      ]);
    }

    // For Kashier, verify the refund was accepted via the response status
    if ($transaction->gateway === 'kashier' && method_exists($refund, 'refundAccepted')) {
      if (! $refund->refundAccepted()) {
        return response()->json([
          'success' => false,
          'error'   => 'Kashier refund was not approved.',
          'raw'     => $refund->json(),
        ]);
      }
    }

    $refund->registerRefund($transaction);
    
    event(new \Larapay\Events\PaymentRefunded($transaction, (array) $refund->json()));

    return response()->json([
      'success'  => true,
      'message'  => "Refund of {$request->amount} {$transaction->currency} submitted successfully.",
      'amount'   => $request->amount,
      'currency' => $transaction->currency,
      'raw'      => $refund->json(),
    ]);
  }

  /**
   * Check a transaction status from the landing page.
   * POST /larapay/check-action  → returns JSON
   */
  public function checkAction(Request $request)
  {
    $transaction = LarapayTransaction::findOrFail((int) $request->transaction_id);
    
    /*if (empty($transaction->refrance)) {
      return response()->json([
        'success' => false,
        'error'   => 'This transaction has no gateway reference yet — it may still be pending a redirect.',
      ]);
    }*/

    $larapay = Larapay::init(gateway: $transaction->gateway);
    $check   = null;

    switch ($transaction->gateway) {
      case 'paytabs':
        $check = $larapay->set(transaction: $transaction)->check();
        break;
      case 'payfort':
        $check = $larapay->set(refrance: $transaction->refrance)->check();
        break;

      case 'paymob':
        // PayMob check: requestToken() then GET transactions/{refrance}
        $check = $larapay->set(refrance: $transaction->refrance)->check();
        break;

      case 'kashier':
        $check     = $larapay->set(transaction: $transaction)->check();
        break;
      default:
        return response()->json([
          'success' => false,
          'error'   => "Check status is not supported for gateway: {$transaction->gateway}",
        ]);
    }

    if ($check->hasError()) {
      return response()->json([
        'success' => false,
        'error'   => $check->getError(),
        'raw'     => $check->json(),
      ]);
    }

    // Update DB status if conclusive
    $json    = $check->json();
    $changed = false;

    if ($check->paymentAccepted() && $transaction->status !== 'success') {
      $transaction->status   = 'success';
      $transaction->response = json_encode($json);
      $changed               = true;
    } elseif ($check->paymentCancelled() && $transaction->status !== 'cancelled') {
      $transaction->status   = 'cancelled';
      $transaction->response = json_encode($json);
      $changed               = true;
    }

    if ($changed) {
      $transaction->save();
    }

    return response()->json([
      'success'   => true,
      'gateway'   => $transaction->gateway,
      'refrance'  => $transaction->refrance,
      'amount'    => $transaction->amount,
      'currency'  => $transaction->currency,
      'db_status' => $transaction->status,
      'updated'   => $changed,
      'raw'       => $json,
    ]);
  }
  /**
   * Unified test-pay dispatcher from the landing page form.
   * POST /larapay/test-pay
   */
  public function testPay(Request $request)
  {
    $request->validate([
      'gateway'          => 'required|string|in:paytabs,paymob,kashier,payfort',
      'action'           => 'nullable|string',
      // cart
      'cart_amount'      => 'required|numeric|min:0.01',
      'cart_currency'    => 'required|string|size:3',
      'cart_description' => 'required|string|max:150',
      // billing
      'billing_name'     => 'required|string|max:80',
      'billing_email'    => 'required|email|max:200',
      'billing_phone'    => 'nullable|string|max:20',
    ]);

    $action   = $request->input('action', 'default');
    $orderId  = uniqid('order_');

    $larapay = Larapay::init(gateway: $request->gateway);

    // ── Apply cart ────────────────────────────────────────────────────────
    $larapay->cart(
      id:          $orderId,
      description: $request->cart_description,
      amount:      (float) $request->cart_amount,
      currency:    strtoupper($request->cart_currency),
    );

    // ── Apply billing (gateway-specific) ──────────────────────────────────
    switch ($request->gateway) {
      case 'paytabs':
        $larapay->billing(
          name:    $request->billing_name,
          email:   $request->billing_email,
          phone:   $request->billing_phone ?? '',
          address: $request->billing_address ?? '',
          city:    $request->billing_city ?? '',
          country: $request->billing_country ?? 'EG',
        );
        break;

      case 'paymob':
        $nameParts = explode(' ', $request->billing_name, 2);
        $larapay->billing(
          first_name: $nameParts[0],
          last_name:  $nameParts[1] ?? $nameParts[0],
          email:      $request->billing_email,
          phone:      $request->billing_phone ?? '',
        );
        $larapay->set(amount: (float) $request->cart_amount);
        break;

      case 'kashier':
        break;

      case 'payfort':
        $larapay->billing(
          name:  $request->billing_name,
          email: $request->billing_email,
          ip:    $request->ip(),
          phone: $request->billing_phone ?? '',
        );
        $larapay->set(currency: strtoupper($request->cart_currency));
        break;
    }

    // ── Dispatch ─────────────────────────────────────────────────────────
    switch ($request->gateway) {
      case 'paytabs':
        if ($action === 'form') {
          return $larapay->getPayForm();
        }
        $pay = $larapay->pay();
        if ($pay->hasError()) { echo $pay->getError(); return; }
        return redirect($pay->getRedirect());

      case 'paymob':
        $pay = $larapay->checkout();
        if ($pay->hasError()) { echo $pay->getError(); return; }
        $pay->register();
        return redirect($pay->getRedirect());

      case 'kashier':
        $larapay->billing(
          name:  $request->billing_name,
          email: $request->billing_email,
          phone: $request->billing_phone ?? '',
        );
        if ($action === 'session') {
          $pay = $larapay->createSession();
          if ($pay->hasError()) {
            return response('<pre>' . htmlspecialchars($pay->getError()) . '</pre>', 500);
          }
          $pay->register();
          return $pay->getPayForm(storeName: config('app.name', 'Store'));
        }
        $pay = $larapay->pay();
        if ($pay->hasError()) { echo $pay->getError(); return; }
        $pay->register();
        return redirect($pay->getRedirect());

      case 'payfort':
        $pay = $larapay->pay();
        if ($pay->hasError()) { echo $pay->getError(); return; }
        $pay->register();
        return $pay->getPayForm();
    }
  }
  public function test()
  {
    $larapay = Larapay::init(gateway: 'paytabs');
    $uid = uniqid();
    $pay = $larapay
                  ->cart(
                    id: uniqid(),
                    description: 'Any description'.uniqid(),  
                    amount: rand()
                  )
                  ->pay();
    if(!$pay->hasError()){
      $pay->register();
      if($pay->hasRedirect()){
        return redirect(route('larapay.run', ['url' => $pay->getRedirect()]));
      }
    }else{
      echo $pay->getError();
    }
  }

  public function paymob()
  {
    $larapay = Larapay::init(gateway: 'paymob');
    $pay = $larapay
                  ->billing(
                    first_name: 'Ahmed',
                    last_name: 'Ahmed',
                    email: 'ahmedsaoud31@gmail.com',
                    phone: '+201148024524',
                  )
                  ->set(amount: 300)
                  //->set(refrance: '219116218')
                  ->checkout();
    if(!$pay->hasError()){
      $pay->register();
      if($pay->hasRedirect()){
        return redirect($pay->getRedirect());
      }
    }else{
      echo $pay->getError();
    }
  }

  public function refund()
  {
    $transaction = LarapayTransaction::whereUid(request()->uid)->firstOrFail();
    $response = json_decode($transaction->response);
    $larapay = new Larapay;
    $check = $larapay
                ->init(gateway: $transaction->gateway)
                ->set(refrance: $transaction->refrance)
                ->cart(
                  id: $response->cart_id,
                  description: $response->cart_description,
                  amount: $response->cart_amount,
                )
                ->refund(rand(100, 5000));
    if(!$check->hasError()){
      if($check->paymentAccepted()){
        $check->registerRefund($transaction);
      }
    }else{
      echo $check->getError();
    }
  }

  public function check()
  {
    $transaction = LarapayTransaction::whereUid(request()->uid)->firstOrFail();
    $larapay = new Larapay;
    $check = $larapay
                ->init(gateway: 'paytabs')
                ->set(refrance: $transaction->refrance)
                ->check();
    if(!$check->hasError()){
      if($check->paymentAccepted()){
        dd($check->json());
        $transaction->status = 'success';
        $transaction->save();
      }
    }else{
      echo $check->getError();
    }
  }

  public function form()
  {
    $larapay = Larapay::init(gateway: 'paytabs');
    if(!$larapay->hasTocken()){
      return $larapay->getPayForm();
    }else{
      $uid = uniqid();
      $pay = $larapay
              ->set(uid: $uid)
              ->billing(
                name: 'Ahmed Aboelsaoud',
                email: 'test@test.com',
                phone: '01010101010',
                address: 'Naser St, Cairo',
                city: 'Cairo',
              )
              ->cart(
                id: rand(10000, 20000),
                description: Str::random(10),
                amount: rand(10000, 5000)
              )
              ->pay();
      dd($pay);
    }
  }

  public function postForm()
  {
    $larapay = Larapay::init(gateway: 'paytabs');
    $pay = $larapay
            ->set(token: request()->token)
            ->customer(
              name: 'Ahmed Aboelsaoud',
              email: 'ahmedsaoud31@gmail.com',
              address: 'Pharaon st, Karnak, LLuxor, Egypt',
              city: 'Luxor',
              country: 'EG',
              ip: '156.203.102.21'
            )
            ->cart(id: 2000, description: 'My Card', amount: 500)
            ->pay();
    if($pay->hasError()){
      echo $pay->getError();
    }else{
      dd($pay->json());
      if($pay->hasRedirect()){
        return redirect($pay->getRedirect());
      }
      echo 'success';
    }
  }

  /**
   * Payfort (Amazon Payment Services) — hosted checkout test page.
   * GET /larapay/payfort
   */
  public function payfort()
  {
    $larapay = Larapay::init(gateway: 'payfort');
    $pay = $larapay
      ->billing(
        email: 'test@example.com',
        name:  'Test User',
        ip:    request()->ip(),
      )
      ->cart(
        id:          uniqid(),
        description: 'Test Payfort order',
        amount:      100.00,
      )
      ->set(currency: 'USD')
      ->pay();

    if ($pay->hasError()) {
      echo $pay->getError();
      return;
    }

    $pay->register();
    return $pay->getPayForm();
  }

  public function kashier()
  {
    /*$larapay = Larapay::init(gateway: 'kashier');
    $pay = $larapay
                  ->billing(
                    name: 'Ahmed Saoud',
                    email: 'test@example.com',
                    phone: '+201000000000',
                  )
                  ->cart(
                    id: uniqid(),
                    description: 'Test Kashier order',
                    amount: 100.00,
                  )
                  ->pay();
    if (!$pay->hasError()) {
      $pay->register();
      if ($pay->hasRedirect()) {
        return redirect($pay->getRedirect());
      }
    } else {
      echo $pay->getError();
    }*/
  }

  /**
   * Kashier Session API — order review + redirect to Kashier hosted payment.
   *
   * GET /larapay/kashier/form
   *   → Creates a payment session via Kashier API
   *   → Shows an order summary page with a "Pay Now" button
   *   → Button links directly to Kashier's hosted session URL
   *   → After payment, Kashier redirects back to larapay.client-callback
   */
  public function kashierForm(Request $request)
  {
    $larapay = Larapay::init(gateway: 'kashier');
    $gateway = $larapay
      ->billing(
        name:  'Test User',
        email: 'test@example.com',
      )
      ->cart(
        id:          uniqid(),
        description: 'Test Kashier API payment',
        amount:      100.00,
      )
      ->createSession();

    if ($gateway->hasError()) {
      return response(
        '<pre style="font-family:monospace;padding:20px">'
        . '<b>Kashier createSession error:</b>' . "\n"
        . htmlspecialchars($gateway->getError()) . "\n\n"
        . '<b>Response body:</b>' . "\n"
        . htmlspecialchars((string) $gateway->responseBody())
        . '</pre>',
        500
      );
    }

    $gateway->register();

    return $gateway->getPayForm(storeName: config('app.name', 'Demo Store'));
  }

  public function serverCallback($gatway)
  {
    $this->clientCallback($gatway);
  }

  public function clientCallback($gatway)
  {
    switch($gatway){
      case 'paytabs':
        $transaction = LarapayTransaction::whereUid(request()->uid)->firstOrFail();
        break;
      case 'paymob':
        $transaction = LarapayTransaction::whereUid(request()->merchant_order_id)->firstOrFail();
        $transaction->refrance = request()->id;
        $transaction->save();
        break;
      case 'kashier':
        $transaction = LarapayTransaction::whereUid(request()->merchantOrderId)->firstOrFail();
        /*if (request()->transactionId && !$transaction->refrance) {
          $transaction->refrance = request()->transactionId;
          $transaction->save();
        }*/
        break;
      case 'payfort':
        // APS POSTs merchant_reference (= our uid) back to return_url
        $transaction = LarapayTransaction::whereUid(request()->post('merchant_reference'))->firstOrFail();
        $larapay = Larapay::init(gateway: 'payfort');
        $verify = $larapay->verifyCallback(request()->post());
        $transaction->refrance = request()->post('fort_id', $transaction->refrance);
        $transaction->response = json_encode(request()->post());
        if (!$verify->hasError() && $verify->paymentAccepted()) {
          $transaction->status = 'success';
          event(new \Larapay\Events\PaymentSucceeded($transaction, (array)$verify->json()));
        } elseif (!$verify->hasError() && $verify->paymentCancelled()) {
          $transaction->status = 'cancelled';
          event(new \Larapay\Events\PaymentFailed($transaction, (array)$verify->json(), 'Cancelled by user'));
        }
        $transaction->save();
        
        return view('larapay::gateways.kashier.result', [
          'status'      => $transaction->status,
          'transaction' => $transaction,
          'storeName'   => config('app.name', 'Store'),
        ]);
    }
    // Generic flow for paytabs / paymob / kashier
    $larapay = Larapay::init(gateway: $transaction->gateway);
    $check = $larapay
                ->set(transaction: $transaction)
                ->check(request()->query());
    if(!$check->hasError()){
      if($check->paymentAccepted()){
        $transaction->status = 'success';
        $transaction->response = json_encode($check->json());
        $transaction->save();
        event(new \Larapay\Events\PaymentSucceeded($transaction, (array)$check->json()));
        return view('larapay::gateways.kashier.result', ['status' => 'success', 'transaction' => $transaction, 'storeName' => config('app.name')]);
      }
      if($check->paymentCancelled()){
        $transaction->status = 'cancelled';
        $transaction->response = json_encode($check->json());
        $transaction->save();
        event(new \Larapay\Events\PaymentFailed($transaction, (array)$check->json(), 'Cancelled by user'));
        return view('larapay::gateways.kashier.result', ['status' => 'cancelled', 'transaction' => $transaction, 'storeName' => config('app.name')]);
      }else{
        event(new \Larapay\Events\PaymentFailed($transaction, (array)$check->json(), 'Failed'));
        return view('larapay::gateways.kashier.result', ['status' => 'failed', 'transaction' => $transaction, 'storeName' => config('app.name')]);
      }
    }else{
      echo $check->getError();
    }
  }
  
}

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

  public function run()
  {
    return redirect((request()->url));
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
    $larapay = Larapay::init(gateway: 'kashier');
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
    }
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
        if (request()->transactionId) {
          $transaction->refrance = request()->transactionId;
          $transaction->save();
        }
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
        } elseif (!$verify->hasError() && $verify->paymentCancelled()) {
          $transaction->status = 'cancelled';
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
                ->set(refrance: $transaction->refrance)
                ->check(request()->query());
    if(!$check->hasError()){
      if($check->paymentAccepted()){
        $transaction->status = 'success';
        $transaction->response = json_encode($check->json());
        $transaction->save();
        return view('larapay::gateways.kashier.result', ['status' => 'success', 'transaction' => $transaction, 'storeName' => config('app.name')]);
      }
      if($check->paymentCancelled()){
        $transaction->status = 'cancelled';
        $transaction->response = json_encode($check->json());
        $transaction->save();
        return view('larapay::gateways.kashier.result', ['status' => 'failed', 'transaction' => $transaction, 'storeName' => config('app.name')]);
      }
    }else{
      echo $check->getError();
    }
  }
  
}

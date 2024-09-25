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
    $larapay = (new Larapay)->init(gateway: 'paytabs');
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
    $larapay = new Larapay;
    $pay = $larapay
            ->init(gateway: 'paytabs')
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

  public function serverCallback()
  {
    file_put_contents('r.json', request()->all());
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
    }
    $larapay = Larapay::init(gateway: $transaction->gateway);
    $check = $larapay
                ->set(refrance: $transaction->refrance)
                ->check();
    if(!$check->hasError()){
      if($check->paymentAccepted()){
        $transaction->status = 'success';
        $transaction->response = json_encode($check->json());
        $transaction->save();
      }
      if($check->paymentCancelled()){
        $transaction->status = 'cancelled';
        $transaction->response = json_encode($check->json());
        $transaction->save();
      }
    }else{
      echo $check->getError();
    }
  }
}

<?php

namespace Larapay\Core\Gateways;

use Illuminate\View\View;
use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;
use Larapay\Models\LarapayTransaction;
use Larapay\Core\Gateways\PayTabs\Traits\Card;
use Larapay\Core\Gateways\PayTabs\Traits\Cart;
use Larapay\Core\Gateways\PayTabs\Traits\Billing;
use Larapay\Core\Gateways\PayTabs\Traits\Customer;
use Larapay\Core\Gateways\PayTabs\Traits\Shipping;

class PayTabs extends LarapayBase implements LarapayInterface
{   
  use Customer, Shipping, Card, Cart, Billing;
  
  public function __construct(
    protected string $gateway,
    protected string $mode,
    protected ?string $provider = null,
    protected ?string $endpoint = null,
    protected ?string $server_key = null,
    protected ?string $client_key = null,
    protected ?float $amount = null, 
    protected ?string $currency = null, 
    protected ?string $profile_id = null, 
    protected ?string $tran_type = null, 
    protected ?string $tran_class = null, 
    protected ?string $server_callback = null, 
    protected ?string $client_callback = null, 
    protected ?string $customer_ref = null, 
    protected ?string $token = null,
    protected ?string $refrance = null,
  )
  {
    $this->gateway = $gateway;
    $this->mode = $mode;
    $this->profile_id = config("larapay.{$this->gateway}.profile_id");
    $this->currency = config("larapay.{$this->gateway}.currency");
    $this->endpoint = config("larapay.{$this->gateway}.endpoint");
    $this->server_key = config("larapay.{$this->gateway}.{$this->mode}.server_key");
    $this->client_key = config("larapay.{$this->gateway}.{$this->mode}.client_key");
    $this->tran_type = 'sale';
    $this->tran_class = 'ecom';
    $this->server_callback = ($config = config("larapay.{$this->gateway}.{$this->mode}.server_callback")) ? $config : route("larapay.server-callback", $this->gateway);
    $this->client_callback = ($config = config("larapay.{$this->gateway}.{$this->mode}.client_callback")) ? $config : route("larapay.client-callback", $this->gateway);
    parent::__construct();
  }

  public function init(): PayTabs
  {
    return $this;
  }

  public function set(
    ?string $uid = null,
    ?string $profile_id = null,
    ?string $tran_type = null,
    ?string $tran_class = null,
    ?string $currency = null,
    ?float $amount = null,
    ?string $cart_id = null,
    ?string $cart_description = null,
    ?string $customer_ref = null,
    ?array $customer_details = null,
    ?bool $hide_shipping = true,
    ?string $server_callback = null,
    ?string $client_callback = null,
    ?string $token = null,
    ?string $refrance = null,
  ): PayTabs
  {
    $this->uid = $uid ?? $this->uid;
    $this->profile_id = $profile_id ?? $this->profile_id;
    $this->tran_type = $tran_type ?? $this->tran_type;
    $this->tran_class = $tran_class ?? $this->tran_class;
    $this->amount = $amount ?? $this->amount;
    $this->cart_id = $cart_id ?? $this->cart_id;
    $this->currency = $currency ?? $this->currency;
    $this->cart_description = $cart_description ?? $this->cart_description;
    $this->customer_ref = $customer_ref ?? $this->customer_ref;
    $this->customer_details = $customer_details ?? $this->customer_details;
    $this->hide_shipping = $hide_shipping ?? $this->hide_shipping;
    $this->server_callback = $server_callback ?? $this->server_callback;
    $this->client_callback = $client_callback ?? $this->client_callback;
    $this->token = $token ?? $this->token;
    $this->refrance = $refrance ?? $this->refrance;
    return $this;
  }

  # Set cart details
  public function cart(
    mixed $id = null,
    ?string $description = null,
    ?string $currency = null,
    ?string $amount = null,
  ): PayTabs
  {
    $this->cart_id = $id ?? $this->cart_id;
    $this->cart_description = $description ?? $this->cart_description;
    $this->currency = $currency ?? $this->currency;
    $this->amount = $amount ?? $this->amount;
    return $this;
  }

  # Run payment
  public function pay($amount = null): PayTabs
  {
    if($this->hasError()) return $this;
    $this->amount = $amount ?? $this->amount;
    if($this->token){
      $this->post($this->getEndPoint('payment/request'), $this->getPostManagedFormData(), $this->getHeaders());
    }else{
      $this->post($this->getEndPoint('payment/request'), $this->getPostHostedFormData(), $this->getHeaders());
    }
    # Set redirect if exists
    if($this->response->successful()){
      if(isset($this->response->json()['redirect_url']) && $this->response->json()['redirect_url']){
        $this->redirect = $this->response->json()['redirect_url'];
      }
    }
    return $this;
  }

  # Refund payment
  public function refund($amount = null): PayTabs
  {
    $this->tran_type = 'refund';
    if($this->hasError()) return $this;
    $this->amount = $amount ?? $this->amount;
    $this->post($this->getEndPoint('payment/request'), $this->getPostRefundFormData(), $this->getHeaders());
    return $this;
  }

  # Check payment
  public function check(): PayTabs
  {
    if(!$this->refrance && !$this->cart_id){
      $this->error = __('Please provide us with Refrance ID or Cart ID');
    }
    if($this->hasError()) return $this;
    $this->post($this->getEndPoint('payment/query'), $this->getPostCheckFormData(), $this->getHeaders());
    return $this;
  }

  # Check payment
  public function getPayForm(): View
  {
    return view('larapay::gateways.paytabs.form', ['clientKey' => $this->getClientKey()]);
  }


  private function getPostManagedFormData(): array
  {
    return array_merge($this->getPostData(),[
      "customer_details" => $this->customer_details,
      "payment_token" => $this->token,
    ]);
  }

  private function getPostHostedFormData(): array
  {
    return array_merge($this->getPostData(),[
      "customer_details" => $this->card_details,
      "payment_token" => $this->token,
    ]);
  }

  private function getPostOwnFormData(): array
  {
    return array_merge($this->getPostData(),[
      "card_details" => $this->card_details
    ]);
  }

  private function getPostCheckFormData(): array
  {
    if($this->refrance){
      return [
        "profile_id" => $this->profile_id,
        "tran_ref" => $this->refrance,
      ];
    }else{
      return [
        "profile_id" => $this->profile_id,
        "cart_id" => $this->cart_id,
      ];
    }
  }

  private function getPostRefundFormData(): array
  {
    return array_merge($this->getPostData(), [
      "profile_id" => $this->profile_id,
      "tran_ref" => $this->refrance,
    ]);
  }

  private function getPostVoidFormData(): array
  {
    return [
      "profile_id" => $this->profile_id,
      "tran_ref" => $this->refrance,
    ];
  }

  private function getPostData(): array
  {
    return [
      'profile_id' => $this->profile_id,
      "tran_type" => $this->tran_type,
      "tran_class" => $this->tran_class,
      "cart_description" => $this->cart_description,
      "cart_id" => $this->cart_id,
      "cart_currency" => $this->currency,
      "cart_amount" => $this->amount,
      "callback" => $this->server_callback, 
      "return" => $this->getClientCallback(),
      "hide_shipping" => $this->hide_shipping
    ];
  }

  private function getHeaders(): array
  {
    return [
      'Authorization' => $this->server_key,
      'Content-Type' => "application/json"
    ];
  }

  public function getClientKey() : string
  {
    return $this->client_key;  
  }

  public function paymentAccepted() : bool
  {
    if(isset($this->json()->payment_result->response_status) && $this->json()->payment_result->response_status == 'A'){
      return true;
    }else{
      return false;
    }
  }

  public function paymentCancelled() : bool
  {
    if(isset($this->json()->payment_result->response_status) && $this->json()->payment_result->response_status == 'C'){
      return true;
    }else{
      return false;
    }
  }

  public function register() : void
  {
    $data = $this->json();
    $transaction = new LarapayTransaction;
    $transaction->type = strtolower($data->tran_type);
    $transaction->uid = $this->uid;
    $transaction->gateway = $this->gateway;
    $transaction->refrance = $data->tran_ref ?? null;
    $transaction->amount = $data->cart_amount ?? 0;
    $transaction->currency = $data->cart_currency ?? null;
    $transaction->response = json_encode($data);
    $transaction->status = 'pending';
    $transaction->save();
  }

  public function registerRefund($parentTransaction) : void
  {
    $data = $this->json();
    $transaction = new LarapayTransaction;
    $transaction->type = strtolower($data->tran_type);
    $transaction->uid = $this->uid;
    $transaction->gateway = $this->gateway;
    $transaction->refrance = $data->tran_ref ?? null;
    $transaction->amount = $data->cart_amount ?? 0;
    $transaction->currency = $data->cart_currency ?? null;
    $transaction->response = json_encode($data);
    $transaction->status = 'success';
    $transaction->parent_id = $parentTransaction->id;
    if(!LarapayTransaction::where('refrance', $transaction->refrance)
                            ->where('gateway', $transaction->gateway)->first()){
      $transaction->save();
    }
  }

  public function hasTocken(): bool
  {
    return request()->has('token');
  }
}

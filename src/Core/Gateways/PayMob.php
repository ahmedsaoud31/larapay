<?php

namespace Larapay\Core\Gateways;

use Exception;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Larapay\Core\LarapayBase;

use Larapay\Core\LarapayInterface;
use Illuminate\Support\Facades\Http;
use Larapay\Models\LarapayTransaction;
use Larapay\Core\Gateways\PayMob\Traits\Billing;

class PayMob extends LarapayBase implements LarapayInterface
{
  use Billing;
  public function __construct(
    protected string $gateway,
    protected string $mode,
    protected ?string $provider = null,
    protected ?string $endpoint = null,
    protected ?string $server_key = null,
    protected ?string $client_key = null,
    protected ?float $amount = null, 
    protected ?float $amount_cents = null, 
    protected ?string $currency = null, 
    protected ?string $profile_id = null, 
    protected ?string $tran_type = null, 
    protected ?string $tran_class = null, 
    protected ?string $server_callback = null, 
    protected ?string $client_callback = null, 
    protected ?string $customer_ref = null, 
    protected ?string $token = null,
    protected ?string $refrance = null,
    protected ?string $country = null,
    protected ?string $expiration = null,
    protected ?array $payment_methods = ['4836197'],
    protected ?array $items = [],
    protected ?array $integrationIDs = null,
  )
  {
    $this->gateway = $gateway;
    $this->mode = $mode;
    $this->profile_id = config("larapay.{$this->gateway}.profile_id");
    $this->currency = config("larapay.currency");
    $this->currency = Str::upper($this->currency);
    $this->api_key = config("larapay.{$this->gateway}.{$this->mode}.api_key");
    $this->secret_key = config("larapay.{$this->gateway}.{$this->mode}.secret_key");
    $this->public_key = config("larapay.{$this->gateway}.{$this->mode}.public_key");
    $this->server_callback = ($config = config("larapay.{$this->gateway}.{$this->mode}.server_callback")) ? $config : route("larapay.server-callback", $this->gateway);
    $this->client_callback = ($config = config("larapay.{$this->gateway}.{$this->mode}.client_callback")) ? $config : route("larapay.client-callback", $this->gateway);
    $this->endpoint = $this->getEndPoint();
    $this->cents = $this->getCents();
    $this->expiration = 5800;
    parent::__construct();
  }

  public function init(): PayMob
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
    ?string $expiration = null,
    ?array $payment_methods = null,
    ?array $items = null,
  ): PayMob
  {
    $this->uid = $uid ?? $this->uid;
    $this->profile_id = $profile_id ?? $this->profile_id;
    $this->tran_type = $tran_type ?? $this->tran_type;
    $this->tran_class = $tran_class ?? $this->tran_class;
    if($amount){
      $this->amount = $amount ?? $this->amount;
      $this->setAmountCents();
    }
    $this->cart_id = $cart_id ?? $this->cart_id ?? null;
    $this->currency = $currency ?? $this->currency;
    $this->currency = Str::upper($this->currency);
    $this->cart_description = $cart_description ?? $this->cart_description ?? null;
    $this->customer_ref = $customer_ref ?? $this->customer_ref;
    $this->customer_details = $customer_details ?? $this->customer_details ?? [];
    $this->hide_shipping = $hide_shipping ?? $this->hide_shipping;
    $this->server_callback = $server_callback ?? $this->server_callback;
    $this->client_callback = $client_callback ?? $this->client_callback;
    $this->token = $token ?? $this->token;
    $this->refrance = $refrance ?? $this->refrance;
    $this->expiration = $expiration ?? $this->expiration;
    $this->payment_methods = $payment_methods ?? $this->payment_methods;
    $this->items = $items ?? $this->items;
    return $this;
  }

  # Set cart details
  public function cart(
    mixed $id = null,
    ?string $description = null,
    ?string $currency = null,
    ?string $amount = null,
  ): PayMob
  {
    $this->cart_id = $id ?? $this->cart_id;
    $this->cart_description = $description ?? $this->cart_description;
    $this->currency = $currency ?? $this->currency;
    $this->amount = $amount ?? $this->amount;
    return $this;
  }

  # Run payment
  public function pay($amount = null): PayMob
  {
    if($this->hasError()) return $this;
    $this->amount = $amount ?? $this->amount;
    $this->post($this->getEndPoint('api/auth/tokens'), $this->getPostTockenData(), $this->getHeaders());
    dd($this->json());
    # Set redirect if exists
    if($this->response->successful()){
      if(isset($this->response->json()['redirect_url']) && $this->response->json()['redirect_url']){
        $this->redirect = $this->response->json()['redirect_url'];
      }
    }
    return $this;
  }

  public function requestToken(): PayMob
  {
    if($this->hasError()) return $this;
    $this->amount = $amount ?? $this->amount;
    $this->post($this->getEndPoint('api/auth/tokens'), $this->getPostTockenData(), $this->getHeaders());
    $this->token = $this->json()->token ?? null;
    if(!$this->token){
      $this->error = __('Token not found');
    }
    return $this;  
  }


  public function checkout(): PayMob
  {
    if($this->hasError()) return $this;
    $this->getIntegrationIDs();
    if($this->hasError()) return $this;
    $this->post($this->getEndPoint('v1/intention'), $this->getPostCheckoutData(), $this->getJsonHeadersWithToken(), options: ['allow_redirects'=> [ 'strict' => true ]]);
    if($this->hasError()) return $this;
    if(!isset($this->json()->client_secret)){
      $this->error = __('Client secret not found');
      return $this;
    }
    $this->redirect = $this->getEndPoint('unifiedcheckout', ['publicKey' => $this->public_key, 'clientSecret' => $this->json()->client_secret]);
    return $this;
  }

  public function check(): PayMob
  {
    if($this->hasError()) return $this;
    $this->requestToken();
    if($this->hasError()) return $this;
    $this->get($this->getEndPoint("api/acceptance/transactions/{$this->refrance}"), [], $this->getJsonHeadersWithBearer());
    return $this;
  }

  public function paymentLink(): PayMob
  {
    $this->requestToken();
    if($this->hasError()) return $this;
    $this->getIntegrationIDs();
    if($this->hasError()) return $this;
    $this->post($this->getEndPoint('api/ecommerce/payment-links'), $this->getPostCheckoutData(), $this->getJsonHeadersWithToken(), options: ['allow_redirects'=> [ 'strict' => true ]]);
    dd($this->json());
    if(!isset($this->json()->payment_keys[0]->key)){
      $this->error = __('Payment key not found');
      return $this;
    }
    if(!isset($this->json()->client_secret)){
      $this->error = __('Client secret not found');
      return $this;
    }
    $this->redirect = $this->getEndPoint('unifiedcheckout', ['publicKey' => $this->json()->payment_keys[0]->key, 'clientSecret' => $this->json()->client_secret]);
    return $this;
    
  }

  public function getIntegrationIDs(): array | null
  {
    if($this->integrationIDs) return $this->integrationIDs;
    $this->requestToken();
    if($this->hasError()) return null;
    $this->get($this->getEndPoint('api/ecommerce/integrations'), $this->getPostIntegrationData(), $this->getJsonHeadersWithBearer());
    if($this->hasError()) return null;
    if(!isset($this->json()->results)){
      $this->error = __('No result to get integration IDs');
      return null;
    }
    $this->integrationIDs = collect($this->json()->results)->pluck('id')->toArray();
    return $this->integrationIDs;
  }

  private function getPostIntegrationData()
  {
    return [
      'is_plugin' => 'true',
      'is_next' => 'yes',
      'page_size' => '500',
      'is_deprecated' => 'false',
      'is_standalone' => 'false',
    ];
  } 

  private function getPostCheckoutData()
  {
    return [
      'amount' => $this->amount_cents,
      'currency' => $this->currency,
      'expiration' => $this->expiration,
      'payment_methods' => $this->payment_methods,
      'items' => $this->items,
      'billing_data' => $this->billing,
      'special_reference' => $this->uid,
      'customer' => [
        'first_name' => $this->billing['first_name'],
        'last_name' => $this->billing['last_name'],
        'email' => $this->billing['email'],
      ],
      'special_reference' => $this->uid,
      'payment_methods' => $this->integrationIDs,
      'notification_url' => $this->server_callback,
      'redirection_url' => $this->client_callback,

    ];

  } 

  private function getPostTockenData()
  {
    return [
      'api_key' => $this->api_key
    ];
  }

  private function getHeaders(): array
  {
    return [
      'Content-Type' => "application/json"
    ];
  }


  private function getJsonHeadersWithBearer(): array
  {
    return [
      'Content-Type' => "application/json",
      'Authorization' => "Bearer {$this->token}"
    ];
  }

  private function getJsonHeadersWithToken(): array
  {
    return [
      'Content-Type' => "application/json",
      'Authorization' => "Token {$this->secret_key}"
    ];
  }

  public function paymentAccepted() : bool
  {
    if(isset($this->json()->success) && $this->json()->success){
      return true;
    }else{
      return false;
    }
  }

  public function paymentCancelled() : bool
  {
    if(isset($this->json()->success) && $this->json()->success){
      return false;
    }else{
      return true;
    }
  }

  public function register() : void
  {
    $data = $this->json();
    $transaction = new LarapayTransaction;
    $transaction->type = strtolower('Sale');
    $transaction->uid = $this->uid;
    $transaction->gateway = $this->gateway;
    $transaction->refrance = $data->special_reference ?? null;
    $transaction->amount = $this->amount ?? 0;
    $transaction->currency = $this->currency ?? null;
    $transaction->response = json_encode($data);
    $transaction->status = 'pending';
    $transaction->save();
  }

  public function registerRefund($parentTransaction) : void
  {
    $data = $this->json();
    $transaction = new LarapayTransaction;
    $transaction->type = strtolower($data->tran_type);
    $transaction->uid = $this->uid ?? uniqid();
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


  public function getEndPoint($path = null, $params = []): string
  {
    $this->endpoint = $this->getDomain();
    return parent::getEndPoint($path, $params);
  }

  public function getDomain()
  {
    if($this->endpoint) return $this->endpoint;
    $domain = 'paymob.com';
    switch($this->getCountryCode()){
      case 'egy':
        $domain = "https://accept.{$domain}";
        break;
      case 'uae':
      case 'are':
        $domain =  "https://uae.{$domain}";
        break;
      case 'pak':
        $domain =  "https://pakistan.{$domain}";
        break;
      case 'omn':
        $domain =  "https://oman.{$domain}";
        break;
      case 'ksa':
      case 'sau':
        $domain =  "https://ksa.{$domain}";
        break;
      default:
        throw new Exception(__("Not vaild country code in secrit key in {$this->gateway} gateway"));
    }
    return $domain;
  }

  public function getCountryCode(?string $key = null): string | null
  {
    $key = $key ?? $this->secret_key;
    return explode('_', $key)[0] ?? null;
  }

  public function getMode(?string $key = null): string | null
  {
    $key = $key ?? $this->secret_key;
    return explode('_', $key)[2] ?? null;
  }

  public function getCents(): int
  {
    return $this->getCountryCode() == 'omn' ? 1000 : 100;
  }

  public function setAmountCents(): void
  {
    $this->amount_cents = round($this->getCents() * $this->amount, 2);
  }
}

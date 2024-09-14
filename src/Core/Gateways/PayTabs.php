<?php

namespace Larapay\Core\Gateways;

use GuzzleHttp\Client;
use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;
use Illuminate\Support\Facades\Http;

class PayTabs extends LarapayBase implements LarapayInterface
{   
  
  public function __construct(
    protected ?string $gateway = 'paytabs',
    protected ?string $provider = null,
    protected ?string $mode = 'sandbox',
    protected ?string $endpoint = null,
    protected ?string $server_key = null,
    protected ?string $client_key = null,
    protected ?float $amount = null, 
    protected ?string $currency = 'usd', 
    protected ?string $profile_id = null, 
    protected ?string $tran_type = null, 
    protected ?string $tran_class = null, 
    protected ?string $callback = null, 
    protected ?string $return = null, 
    protected ?string $cart_id = null, 
    protected ?string $cart_description = null, 
    protected ?string $customer_ref = null, 
    protected ?array $customer_details = null, 
    protected array $card_details = [],
    protected ?bool $hide_shipping = true,
  )
  {
    $this->gateway = $gateway;
    $this->mode = config("larapay.mode");
    $this->profile_id = config("larapay.{$this->gateway}.profile_id");
    $this->endpoint = config("larapay.{$this->gateway}.endpoint");
    $this->server_key = config("larapay.{$this->gateway}.{$this->mode}.server_key");
    $this->client_key = config("larapay.{$this->gateway}.{$this->mode}.client_key");
    $this->tran_type = 'sale';
    $this->tran_class = 'ecom';
    $this->callback = config("larapay.{$this->gateway}.callback");
    $this->return = config("larapay.{$this->gateway}.return");
    $this->customer_details = [
      'name' => null,
      'email' => null,
      'phone' => null,
      'street1' => null,
      'city' => null,
      'country' => null,
      'state' => null,
      'zip' => null,
      'ip' => null,
    ];
    $this->card_details = [
      'pan' => null,
      'expiry_month' => null,
      'expiry_year' => null,
      'cvv' => null,
    ];
  }

  public function set(
    ?string $mode = null,
    ?string $profile_id = null,
    ?string $tran_type = null,
    ?string $tran_class = null,
    ?string $cart_id = null,
    ?string $currency = null,
    ?string $cart_currency = null,
    ?float $amount = null,
    ?float $cart_amount = null,
    ?string $cart_description = null,
    ?string $customer_ref = null,
    ?array $customer_details = null,
    ?bool $hide_shipping = true,
    ?string $callback = null,
    ?string $return = null,
  ): PayTabs
  {
    $this->mode = $mode ?? $this->mode;
    $this->profile_id = $profile_id ?? $this->profile_id;
    $this->tran_type = $tran_type ?? $this->tran_type;
    $this->tran_class = $tran_class ?? $this->tran_class;
    $this->amount = $amount ?? $this->amount;
    $this->cart_id = $cart_id ?? $this->cart_id;
    $this->currency = $cart_currency ?? $this->currency;
    $this->currency = $currency ?? $this->currency;
    $this->amount = $cart_amount ?? $this->amount;
    $this->cart_description = $cart_description ?? $this->cart_description;
    $this->customer_ref = $customer_ref ?? $this->customer_ref;
    $this->customer_details = $customer_details ?? $this->customer_details;
    $this->hide_shipping = $hide_shipping ?? $this->hide_shipping;
    $this->callback = $callback ?? $this->callback;
    $this->return = $return ?? $this->return;
    return $this;
  }

  public function customer(
    ?string $name = null,
    ?string $email = null,
    ?string $phone = null,
    ?string $street1 = null,
    ?string $city = null,
    ?string $country = null,
    ?string $state = null,
    ?string $zip = null,
    ?string $ip = null,
  ): PayTabs
  {
    $this->customer_details['name'] = $name ?? $this->customer_details['name'];
    $this->customer_details['email'] = $email ?? $this->customer_details['email'];
    $this->customer_details['phone'] = $phone ?? $this->customer_details['phone'];
    $this->customer_details['street1'] = $street1 ?? $this->customer_details['street1'];
    $this->customer_details['city'] = $city ?? $this->customer_details['city'];
    $this->customer_details['country'] = $country ?? $this->customer_details['country'];
    $this->customer_details['state'] = $state ?? $this->customer_details['state'];
    $this->customer_details['zip'] = $zip ?? $this->customer_details['zip'];
    $this->customer_details['ip'] = $ip ?? $this->customer_details['ip'];
    return $this;
  }

  public function card(
    ?string $number = null,
    ?int $month = null,
    ?int $year = null,
    ?string $cvv = null,
  ): PayTabs
  {
    $this->card_details['pan'] = $number ?? $this->card_details['pan'];
    $this->card_details['expiry_month'] = $month ?? $this->card_details['expiry_month'];
    $this->card_details['expiry_year'] = $year ?? $this->card_details['expiry_year'];
    $this->card_details['cvv'] = $cvv ?? $this->card_details['cvv'];
    return $this;
  }

  public function pay($amount = null): PayTabs
  {
    $this->amount = $amount ?? $this->amount;
    $this->post($this->endpoint, $this->getPostData(), $this->getHeaders());
    return $this;
  }

  public function test(): PayTabs
  {
    $this->errors[] = 'Bad gateway';
    return $this;
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
      "callback" => $this->callback, 
      "return" => $this->return,
      "card_details" => $this->card_details
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

}

<?php

namespace Larapay\Core;

class Larapay
{   
  public function __construct(
    protected ?string $gateway = 'paypal',
    protected ?string $mode = 'sandbox',
    protected ?string $provider = null,
    protected ?string $name = null, 
    protected ?string $email = null, 
    protected ?string $first_name = null, 
    protected ?string $last_name = null, 
    protected ?float $amount = null, 
    protected ?float $profile_id = null, 
    protected ?float $tran_type = null, 
    protected ?float $tran_class = null, 
    protected ?float $callback = null, 
    protected ?float $return = null, 
  )
  {
    $this->gateway = config("larapay.gateway");
    $this->mode = config("larapay.mode");
    $this->profile_id = config("larapay.paytabs.profile_id");
    $this->tran_type = 'sale';
    $this->tran_class = 'ecom';
    $this->callback = config("larapay.paytabs.callback");
    $this->return = config("larapay.paytabs.return");
  }
  
  public function config(
      $gateway = null,
      $provider = null,
      $mode = null,
      $profile_id = null,
      $callback = null,
      $return = null,
    ): Larapay
  {
    $this->gateway = $gateway ?? $this->gateway;
    $this->provider = $provider ?? $this->provider;
    $this->mode = $mode ?? $this->mode;
    $this->profile_id = $profile_id ?? $this->profile_id;
    $this->callback = $callback ?? $this->callback;
    $this->return = $return ?? $this->return;
    return $this;
  } 

  public function set(
      $name = null,
      $email = null,
      $first_name = null,
      $last_name = null,
      $amount = null,
      $cart_id = null,
      $cart_currency = null,
      $cart_amount = null,
      $cart_description = null,
    ): Larapay
  {
    $this->name = $name ?? $this->name;
    $this->email = $email ?? $this->email;
    $this->first_name = $first_name ?? $this->first_name;
    $this->last_name = $last_name ?? $this->last_name;
    $this->amount = $amount ?? $this->amount;
    return $this;
  }
}

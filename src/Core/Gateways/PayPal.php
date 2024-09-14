<?php

namespace Larapay\Core\Gateways;

use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;

class PayPal extends LarapayBase implements LarapayInterface
{   
  
  public function __construct(
    protected ?string $gateway = 'paypal',
    protected ?string $provider = null,
    protected ?string $mode = 'sandbox',
    protected ?float $amount = null, 
    protected ?string $profile_id = null, 
    protected ?string $tran_type = null, 
    protected ?string $tran_class = null, 
    protected ?string $callback = null, 
    protected ?string $return = null, 
    protected ?string $cart_id = null, 
    protected ?string $cart_currency = null, 
    protected ?string $cart_amount = null, 
    protected ?string $cart_description = null, 
  )
  {
    $this->gateway = $gateway;
    $this->mode = config("larapay.mode");
    $this->profile_id = config("larapay.{$this->gateway}.profile_id");
    $this->tran_type = 'sale';
    $this->tran_class = 'ecom';
    $this->callback = config("larapay.{$this->gateway}.callback");
    $this->return = config("larapay.{$this->gateway}.return");
  }
  public function set(
    $mode = null,
    $amount = null,
    $cart_id = null,
    $cart_currency = null,
    $cart_amount = null,
    $cart_description = null,
  ): PayPal
  {
    $this->mode = $mode ?? $this->mode;
    $this->amount = $amount ?? $this->amount;
    $this->cart_id = $cart_id ?? $this->cart_id;
    $this->cart_currency = $cart_currency ?? $this->cart_currency;
    $this->cart_amount = $cart_amount ?? $this->cart_amount;
    $this->cart_description = $cart_description ?? $this->cart_description;
    return $this;
  }

  public function test(): void
  {
    dd($this->tran_type);
  }
}

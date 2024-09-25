<?php
namespace Larapay\Core\Gateways\PayMob\Traits;

use Larapay\Core\Gateways\PayMob;

trait Cart
{
  
  protected ?string $cart_id = null; 
  protected ?string $cart_description = null;

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
}
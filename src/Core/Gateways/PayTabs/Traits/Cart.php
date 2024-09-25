<?php
namespace Larapay\Core\Gateways\PayTabs\Traits;

use Larapay\Core\Gateways\PayTabs;

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
  ): PayTabs
  {
    $this->cart_id = $id ?? $this->cart_id;
    $this->cart_description = $description ?? $this->cart_description;
    $this->currency = $currency ?? $this->currency;
    $this->amount = $amount ?? $this->amount;
    return $this;
  }
}
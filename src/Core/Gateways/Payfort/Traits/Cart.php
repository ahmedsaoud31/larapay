<?php

namespace Larapay\Core\Gateways\Payfort\Traits;

use Larapay\Core\Gateways\Payfort;

trait Cart
{
    protected mixed   $cart_id          = null;
    protected ?string $cart_description = null;

    public function cart(
        mixed   $id          = null,
        ?string $description = null,
        ?string $currency    = null,
        ?float  $amount      = null,
    ): Payfort {
        $this->cart_id          = $id          ?? $this->cart_id;
        $this->cart_description = $description ?? $this->cart_description;
        $this->currency         = $currency    ?? $this->currency;
        $this->amount           = $amount      ?? $this->amount;
        return $this;
    }
}

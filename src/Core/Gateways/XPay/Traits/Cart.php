<?php

namespace Larapay\Core\Gateways\XPay\Traits;

trait Cart
{
    protected mixed   $cart_id          = null;
    protected ?string $cart_description = null;

    /**
     * Set order / cart details.
     */
    public function cart(
        mixed   $id          = null,
        ?string $description = null,
        ?string $currency    = null,
        ?float  $amount      = null,
    ): static {
        $this->cart_id          = $id          ?? $this->cart_id;
        $this->cart_description = $description ?? $this->cart_description;
        $this->currency         = $currency    ? strtoupper($currency) : $this->currency;
        $this->amount           = $amount      ?? $this->amount;
        return $this;
    }
}


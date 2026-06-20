<?php

namespace Larapay\Core\Gateways\Kashier\Traits;

use Larapay\Core\Gateways\Kashier;

trait Billing
{
    protected array $billing = [
        'name'    => null,
        'email'   => null,
        'phone'   => null,
        'address' => null,
        'city'    => null,
        'country' => null,
    ];

    /**
     * Set customer / billing details.
     * These are passed as display info to the Kashier hosted page.
     */
    public function billing(
        ?string $name    = null,
        ?string $email   = null,
        ?string $phone   = null,
        ?string $address = null,
        ?string $city    = null,
        ?string $country = null,
    ): Kashier {
        $this->billing['name']    = $name    ?? $this->billing['name'];
        $this->billing['email']   = $email   ?? $this->billing['email'];
        $this->billing['phone']   = $phone   ?? $this->billing['phone'];
        $this->billing['address'] = $address ?? $this->billing['address'];
        $this->billing['city']    = $city    ?? $this->billing['city'];
        $this->billing['country'] = $country ?? $this->billing['country'];
        return $this;
    }
}

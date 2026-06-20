<?php

namespace Larapay\Core\Gateways\Payfort\Traits;

use Larapay\Core\Gateways\Payfort;

trait Billing
{
    protected string $customer_email = '';
    protected string $customer_name  = '';
    protected string $customer_ip    = '';
    protected string $phone_number   = '';

    public function billing(
        ?string $email  = null,
        ?string $name   = null,
        ?string $ip     = null,
        ?string $phone  = null,
    ): Payfort {
        $this->customer_email = $email ?? $this->customer_email;
        $this->customer_name  = $name  ?? $this->customer_name;
        $this->customer_ip    = $ip    ?? $this->customer_ip;
        $this->phone_number   = $phone ?? $this->phone_number;
        return $this;
    }
}

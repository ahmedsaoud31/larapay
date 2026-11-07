<?php

namespace Larapay\Core\Gateways\XPay\Traits;

trait Billing
{
    protected array $billing = [
        'name'  => null,
        'email' => null,
        'phone' => null,
    ];

    /**
     * Set customer / billing details.
     *
     * XPay requires real customer data:
     *  - name  : at least two words (first + last name)
     *  - email : valid email
     *  - phone : international format with country code (e.g. +201000000000)
     */
    public function billing(
        ?string $name  = null,
        ?string $email = null,
        ?string $phone = null,
    ): static {
        $this->billing['name']  = $name  ?? $this->billing['name'];
        $this->billing['email'] = $email ?? $this->billing['email'];
        $this->billing['phone'] = $phone ?? $this->billing['phone'];
        return $this;
    }

    /**
     * Build the billing_data payload expected by XPay, normalising
     * the name (min. 2 words) and phone (country-code prefixed).
     */
    protected function billingPayload(): array
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $this->billing['name']));
        if ($name === '') {
            $name = 'Valued Customer';
        } elseif (! str_contains($name, ' ')) {
            $name .= ' Customer';
        }

        $phone = preg_replace('/[^\d+]/', '', (string) $this->billing['phone']);
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        } elseif ($phone !== '' && ! str_starts_with($phone, '+')) {
            // Local Egyptian number (01xxxxxxxxx) -> +201xxxxxxxxx, otherwise just prefix "+"
            $phone = preg_match('/^01\d{9}$/', $phone) ? '+2' . $phone : '+' . $phone;
        }

        return [
            'name'         => $name,
            'email'        => (string) $this->billing['email'],
            'phone_number' => $phone,
        ];
    }
}


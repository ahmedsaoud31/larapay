<?php
namespace Larapay\Core\Gateways\PayTabs\Traits;

use Larapay\Core\Gateways\PayTabs;

trait Billing
{
  # Set billing details
  public function billing(
    ?string $name = null,
    ?string $email = null,
    ?string $phone = null,
    ?string $street1 = null,
    ?string $address = null,
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
    $this->customer_details['street1'] = $address ?? $this->customer_details['street1'];
    $this->customer_details['city'] = $city ?? $this->customer_details['city'];
    $this->customer_details['country'] = $country ?? $this->customer_details['country'];
    $this->customer_details['state'] = $state ?? $this->customer_details['state'];
    $this->customer_details['zip'] = $zip ?? $this->customer_details['zip'];
    $this->customer_details['ip'] = $ip ?? $this->customer_details['ip'];
    return $this;
  }

}
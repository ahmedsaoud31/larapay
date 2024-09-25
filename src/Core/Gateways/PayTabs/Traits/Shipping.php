<?php
namespace Larapay\Core\Gateways\PayTabs\Traits;

use Larapay\Core\Gateways\PayTabs;

trait Shipping
{
  protected ?bool $hide_shipping = true;
  protected array $shipping_details =[
                                      'name' => null,
                                      'email' => null,
                                      'phone' => null,
                                      'street1' => null,
                                      'city' => null,
                                      'country' => null,
                                      'state' => null,
                                      'zip' => null,
                                      'ip' => null,
                                    ];

  # Set billing details
  public function shipping(
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
    $this->hide_shipping = false;
    $this->shipping_details['name'] = $name ?? $this->shipping_details['name'];
    $this->shipping_details['email'] = $email ?? $this->shipping_details['email'];
    $this->shipping_details['phone'] = $phone ?? $this->shipping_details['phone'];
    $this->shipping_details['street1'] = $street1 ?? $this->shipping_details['street1'];
    $this->shipping_details['street1'] = $address ?? $this->shipping_details['street1'];
    $this->shipping_details['city'] = $city ?? $this->shipping_details['city'];
    $this->shipping_details['country'] = $country ?? $this->shipping_details['country'];
    $this->shipping_details['state'] = $state ?? $this->shipping_details['state'];
    $this->shipping_details['zip'] = $zip ?? $this->shipping_details['zip'];
    $this->shipping_details['ip'] = $ip ?? $this->shipping_details['ip'];
    return $this;
  }
}
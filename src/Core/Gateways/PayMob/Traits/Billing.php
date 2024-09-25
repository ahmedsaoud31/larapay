<?php
namespace Larapay\Core\Gateways\PayMob\Traits;

use Larapay\Core\Gateways\PayMob;

trait Billing
{
  protected array $billing = [
                                'apartment' => 'N/A',
                                'first_name' => null,
                                'last_name' => null,
                                'street' => 'N/A',
                                'building' => 'N/A',
                                'phone_number' => null,
                                'country' => 'N/A',
                                'email' => null,
                                'floor' => 'N/A',
                                'state' => 'N/A',
                                'shipping_method' => 'N/A',
                                'postal_code' => 'N/A',
                                'city' => 'N/A',
                              ];
  # Set billing details
  public function billing(
    ?string $apartment = null,
    
    ?string $first_name = null,
    ?string $last_name = null,
    ?string $street = null,
    ?string $building = null,
    ?string $phone_number = null,
    ?string $country = null,
    ?string $email = null,
    ?string $state = null,
    ?string $floor = null,

    ?string $name = null,
    ?string $phone = null,
    ?string $address = null,
    ?string $city = null,
  ): PayMob
  {
    $this->billing['apartment'] = $apartment ?? $this->billing['apartment'];
    $this->billing['first_name'] = $first_name ?? $this->billing['first_name'];
    $this->billing['last_name'] = $last_name ?? $this->billing['last_name'];
    $this->billing['street'] = $street ?? $this->billing['street'];
    $this->billing['building'] = $building ?? $this->billing['building'];
    $this->billing['phone_number'] = $phone_number ?? $this->billing['phone_number'];
    $this->billing['country'] = $country ?? $this->billing['country'];
    $this->billing['email'] = $email ?? $this->billing['email'];
    $this->billing['state'] = $state ?? $this->billing['state'];
    $this->billing['floor'] = $floor ?? $this->billing['floor'];
    $this->billing['city'] = $city ?? $this->billing['city'];

    if($name){
      if(!$this->billing['first_name']){
        $this->billing['first_name'] = explode(' ', $name)[0] ?? null;
      }
      if(!$this->billing['last_name']){
        $this->billing['last_name'] = explode(' ', $name)[0] ?? null;
      }
    }
    $this->billing['phone_number'] = $phone ?? $this->billing['phone_number'];
    $this->billing['street'] = $address ?? $this->billing['street'];
    
    return $this;
  }

}
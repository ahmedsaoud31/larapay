<?php
namespace Larapay\Core\Gateways\PayMob\Traits;

use Larapay\Core\Gateways\PayMob;

trait Card
{
  protected array $card_details = [
                                  'pan' => null,
                                  'expiry_month' => null,
                                  'expiry_year' => null,
                                  'cvv' => null,
                                ];
  
  # Set card details
  public function card(
    ?string $number = null,
    ?int $month = null,
    ?int $year = null,
    ?string $cvv = null,
  ): PayMob
  {
    $this->card_details['pan'] = $number ?? $this->card_details['pan'];
    $this->card_details['expiry_month'] = $month ?? $this->card_details['expiry_month'];
    $this->card_details['expiry_year'] = $year ?? $this->card_details['expiry_year'];
    $this->card_details['cvv'] = $cvv ?? $this->card_details['cvv'];
    return $this;
  }
}
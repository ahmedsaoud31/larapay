<?php

namespace Larapay;

use Larapay\Core\Gateways\PayPal;
use Larapay\Core\Gateways\PayTabs;

class Larapay
{   
  public function __construct(
    protected $model = null,
    protected ?string $gateway = 'paypal',
    protected ?string $mode = 'sandbox',
  )
  {
    $this->gateway = config("larapay.gateway");
    $this->setGateway($this->gateway);
    $this->mode = config("larapay.mode");
  }

  public function init($gateway = null, $mode = null)
  {
    $this->gateway = $gateway ?? $this->gateway;
    $this->mode = $mode ?? $this->mode;
    $this->setGateway($this->gateway);
    return $this->model;
  }

  private function setGateway($gateway): void
  {
    switch($gateway){
      case "paypal":
        $this->model = (new PayPal)->set(mode: $this->mode);
        break;
      case "paytabs":
        $this->model =  (new PayTabs)->set(mode: $this->mode);
        break;
      default:
        $this->model =  (new PayPal)->set(mode: $this->mode);
    }
  }
}

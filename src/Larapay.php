<?php

namespace Larapay;

use Larapay\Core\Exceptions\NotSupportedGatewayException;
use Larapay\Core\Exceptions\NotSupportedModeException;
use Larapay\Core\Gateways\PayPal;
use Larapay\Core\Gateways\PayTabs;
use Larapay\Core\Gateways\PayMob;


class Larapay
{   
  public function __construct(
    protected $model = null,
    protected ?string $gateway = null,
    protected ?string $mode = null,
  )
  {
    $this->gateway = config("larapay.gateway") ?? 'paypal';
    $this->mode = config("larapay.mode") ?? 'sandbox';
    $this->setGateway($this->gateway);
  }

  public function init($gateway = null, $mode = null)
  {
    $this->gateway = $gateway ?? $this->gateway;
    $this->mode = $mode ?? $this->mode;
    $this->checkSupported();
    $this->setGateway();
    return $this->model;
  }

  private function setGateway(): void
  {
    switch($this->gateway){
      case "paypal":
        $this->model = (new PayPal(gateway: $this->gateway, mode: $this->mode))->init();
        break;
      case "paytabs":
        $this->model =  (new PayTabs(gateway: $this->gateway, mode: $this->mode))->init();
        break;
      case "paymob":
        $this->model =  (new PayMob(gateway: $this->gateway, mode: $this->mode))->init();
        break;
      default:
        $this->model =  (new PayPal(gateway: $this->gateway, mode: $this->mode))->init();
    }
  }
  
  private function checkSupported()
  {
    $this->checkSupportedGateway();
    $this->checkSupportedMode();
  }

  private function checkSupportedGateway() : void
  {
    if(!in_array($this->gateway, config('larapay.gateways'))) throw new NotSupportedGatewayException($this->gateway);
  }

  private function checkSupportedMode() : void
  {
    if(!in_array($this->mode, ['live', 'sandbox'])) throw new NotSupportedModeException($this->mode);
  }

}

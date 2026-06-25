<?php

namespace Larapay\Core\Exceptions;

use Exception;

class NotSupportedGatewayException extends Exception
{
    public ?string $msg = null;
    
    public function __construct($gateway)
    {
      $this->msg = __("{$gateway} gateway not supported yet, use one of this list (". implode(', ', config('larapay.gateways')) .")");
      parent::__construct($this->msg);
    }
}
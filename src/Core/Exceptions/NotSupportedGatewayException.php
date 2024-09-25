<?php

namespace Larapay\Core\Exceptions;

use Exception;

class NotSupportedGatewayException extends Exception
{
    public function __construct($gateway)
    {
      parent::__construct(__("{$gateway} gateway not supported yet, use one of this list (". implode(', ', config('larapay.gateways')) .")"));
    }
}
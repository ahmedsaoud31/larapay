<?php

namespace Larapay\Core\Exceptions;

use Exception;

class GatewayConnectionException extends Exception
{
    public function __construct($gateway)
    {
      parent::__construct(__("Connecting to {$gateway} gateway faild"));
    }
}
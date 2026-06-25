<?php

namespace Larapay\Core\Exceptions;

use Exception;

class GatewayConnectionException extends Exception
{
    public ?string $msg = null;

    public function __construct($gateway)
    {
      $this->msg = __("Connecting to {$gateway} gateway faild");
      parent::__construct($this->msg);
    }
}
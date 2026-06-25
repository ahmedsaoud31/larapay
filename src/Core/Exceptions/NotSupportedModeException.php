<?php

namespace Larapay\Core\Exceptions;

use Exception;

class NotSupportedModeException extends Exception
{
    public ?string $msg = null;
    
    public function __construct($gateway)
    {
      $this->msg = __("{$mode} mode not supported, Use live or sandbox modes only");
      parent::__construct($this->msg);
    }
}
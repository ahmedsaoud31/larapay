<?php

namespace Larapay\Core\Exceptions;

use Exception;

class NotSupportedModeException extends Exception
{
    public function __construct($mode)
    {
      parent::__construct(__("{$mode} mode not supported, Use live or sandbox modes only"));
    }
}
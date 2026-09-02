<?php

namespace Larapay\Core\Exceptions;

use Exception;

class GatewayConfigurationException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = "Gateway configuration error", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace Larapay\Core\Exceptions;

use Exception;

class PaymentGatewayException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = "Payment gateway error occurred", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

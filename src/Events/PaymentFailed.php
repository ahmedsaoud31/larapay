<?php

namespace Larapay\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Larapay\Models\LarapayTransaction;

class PaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public LarapayTransaction $transaction,
        public array $gatewayResponse = [],
        public string $reason = ''
    ) {}
}

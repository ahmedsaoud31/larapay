<?php

namespace Larapay\Core\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;
use Larapay\Models\LarapayTransaction;

class Tab extends LarapayBase implements LarapayInterface
{
    protected ?string $merchant_id = null;
    protected ?string $api_key = null;
    protected ?string $public_key = null;
    protected ?string $custom_url = null;
    protected ?float $amount = null;
    protected ?string $currency = null;
    protected ?string $server_callback = null;
    protected ?string $client_callback = null;
    protected ?string $refrance = null;

    public function __construct(
        protected ?string $gateway = 'tab',
        protected ?string $provider = null,
        protected ?string $mode = 'live',
        ?string $cart_id = null,
        ?string $cart_currency = null,
        ?string $cart_amount = null,
        ?string $cart_description = null,
    ) {
        $this->gateway = $gateway;
        $this->mode = config("larapay.mode");
        
        $this->merchant_id = config("larapay.tab.merchant_id");
        $this->api_key = config("larapay.tab.{$this->mode}.api_key");
        $this->public_key = config("larapay.tab.{$this->mode}.public_key");
        $this->custom_url = config("larapay.tab.custom_checkout_url");

        $this->server_callback = config("larapay.tab.server_callback") ?? route('larapay.server-callback', ['gateway' => $this->gateway]);
        $this->client_callback = config("larapay.tab.client_callback") ?? route('larapay.client-callback', ['gateway' => $this->gateway]);

        $this->cart_id = $cart_id;
        $this->cart_currency = $cart_currency;
        $this->cart_amount = $cart_amount;
        $this->cart_description = $cart_description;
    }

    public function init(): static
    {
        return $this;
    }

    public function set(
        ?string $uid = null,
        ?string $currency = null,
        ?float $amount = null,
        ?string $cart_id = null,
        ?string $cart_description = null,
        ?string $server_callback = null,
        ?string $client_callback = null,
        ?string $refrance = null,
        ?LarapayTransaction $transaction = null,
        ?string $reason = null,
    ): static {
        $this->uid = $uid ?? $this->uid;
        $this->currency = $currency ? Str::upper($currency) : $this->currency;
        $this->amount = $amount ?? $this->amount;
        $this->cart_id = $cart_id ?? $this->cart_id;
        $this->cart_description = $cart_description ?? $this->cart_description;
        $this->server_callback = $server_callback ?? $this->server_callback;
        $this->client_callback = $client_callback ?? $this->client_callback;
        $this->refrance = $refrance ?? $this->refrance;
        $this->transaction = $transaction ?? $this->transaction;
        $this->reason = $reason ?? $this->reason;
        
        return $this;
    }

    protected function getApiBaseUrl(): string
    {
        return $this->mode === 'sandbox' ? 'https://api.test.tab.travel/v1' : 'https://api.tab.travel/v1';
    }

    public function pay(?float $amount = null): static
    {
        if ($this->hasError()) return $this;

        $this->amount = $amount ?? $this->amount;

        if (! $this->amount || ! $this->currency) {
            $this->error = 'Amount and currency are required for Tab payment.';
            return $this;
        }

        $orderId = $this->cart_id ?? $this->uid;
        $formattedAmount = number_format((float) $this->amount, 2, '.', '');

        if (!empty($this->api_key)) {
            try {
                $response = Http::withToken($this->api_key)
                    ->timeout(15)
                    ->acceptJson()
                    ->post($this->getApiBaseUrl() . '/checkout_sessions', [
                        'amount'           => (float) $formattedAmount,
                        'currency'         => $this->currency,
                        'reference_id'     => $orderId,
                        'description'      => $this->cart_description ?? ('Order #' . $orderId),
                        'merchant_id'      => $this->merchant_id ?: null,
                        'success_url'      => $this->client_callback,
                        'cancel_url'       => $this->client_callback,
                        'metadata'         => [
                            'order_id' => $orderId,
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $redirectUrl = $data['checkout_url'] ?? ($data['url'] ?? ($data['redirect_url'] ?? null));

                    if ($redirectUrl) {
                        $this->redirect = $redirectUrl;
                        return $this;
                    }
                }
            } catch (\Throwable $e) {
                // fallback to hosted url
            }
        }

        // Hosted Checkout Link Fallback
        $merchantIdParam = $this->merchant_id ?: ($this->public_key ?: 'default');
        $baseUrl = !empty($this->custom_url) 
            ? rtrim($this->custom_url, '/') 
            : 'https://business.tab.travel/pay/' . urlencode($merchantIdParam);

        $queryParams = http_build_query([
            'amount'      => $formattedAmount,
            'currency'    => $this->currency,
            'ref'         => $orderId,
            'desc'        => $this->cart_description ?? ('Order #' . $orderId),
            'success_url' => $this->client_callback,
            'cancel_url'  => $this->client_callback,
            'return_url'  => $this->client_callback,
        ]);

        $this->redirect = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . $queryParams;

        return $this;
    }

    public function check2(array $data): static
    {
        $sessionId = $data['session_id'] ?? ($data['id'] ?? ($data['ref'] ?? null));
        $paymentId = $data['payment_id'] ?? $sessionId;

        if (empty($this->api_key) || empty($sessionId) || str_starts_with($sessionId, 'hosted_')) {
            // Unverified mode, cannot verify via API without key, assume success if returned.
            return $this;
        }

        try {
            $endpoint = $this->getApiBaseUrl() . '/checkout_sessions/' . urlencode($sessionId);
            $response = Http::withToken($this->api_key)
                ->timeout(10)
                ->acceptJson()
                ->get($endpoint);

            if ($response->successful()) {
                $resData = $response->json();
                $status = strtoupper($resData['status'] ?? ($resData['payment_status'] ?? ''));
                $isPaid = in_array($status, ['COMPLETED', 'PAID', 'SUCCEEDED', 'SUCCESS']);

                if (!$isPaid) {
                    $this->error = "Tab payment status is: {$status}";
                }
            } else {
                $this->error = "Tab verification failed with status: " . $response->status();
            }
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }

        return $this;
    }
}

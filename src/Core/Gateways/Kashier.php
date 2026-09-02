<?php

namespace Larapay\Core\Gateways;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;
use Larapay\Models\LarapayTransaction;
use Larapay\Core\Gateways\Kashier\Traits\Cart;
use Larapay\Core\Gateways\Kashier\Traits\Billing;

/**
 * Kashier.io payment gateway.
 *
 * Two integration modes:
 *
 *  A) HPP Redirect  —  pay() → getRedirect()
 *     Pure signed URL redirect to Kashier's hosted page. No API call needed.
 *
 *  B) API / Custom Form  —  createSession() → getPayForm() → chargeSession()
 *     1. createSession()  POST /v3/payment/sessions  →  stores $session_id
 *     2. getPayForm()     returns your own Blade card-entry form
 *     3. chargeSession()  POST card data to the session charge endpoint
 *     4. checkSession()   GET  session status (poll / webhook verify)
 *
 * Config keys  (config/larapay.php → 'kashier'):
 *   mid                – Merchant ID  e.g. "MID-1234-567"
 *   live.api_key       – Live Payment API key  (hash + signature)
 *   live.secret_key    – Live Secret key       (Authorization header)
 *   sandbox.api_key    – Test Payment API key
 *   sandbox.secret_key – Test Secret key
 *   server_callback    – Server webhook override  (null = auto-route)
 *   client_callback    – Redirect-back URL override (null = auto-route)
 *   allowed_methods    – "card,wallet,bank_installments"
 *   display            – "en" or "ar"
 *
 * @see https://developers.kashier.io/payment/payment-sessions
 */
class Kashier extends LarapayBase implements LarapayInterface
{
    use Cart, Billing;

    protected const BASE_URL = 'https://checkout.kashier.io';
    protected const API_LIVE = 'https://api.kashier.io';
    protected const API_TEST = 'https://test-api.kashier.io';

    // Refund endpoints (different subdomain from session API)
    protected const REFUND_LIVE = 'https://fep.kashier.io';
    protected const REFUND_TEST = 'https://test-fep.kashier.io';

    protected ?string $mid             = null;
    protected ?string $api_key         = null;
    protected ?string $secret_key      = null;
    protected ?string $endpoint        = null;
    protected ?float  $amount          = null;
    protected ?string $currency        = null;
    protected ?string $server_callback = null;
    protected ?string $client_callback = null;
    protected ?string $refrance        = null;
    protected ?string $allowed_methods = null;
    protected ?string $display         = null;
    protected ?LarapayTransaction  $transaction         = null;
    protected ?string  $reason         = null;

    protected ?string $session_id          = null;
    protected ?string $session_url         = null;
    protected array   $callbackData        = [];
    protected array   $allowed_methods_arr = [];
    protected array $acceptedSuccessStatus = ['SUCCESS', 'PAID', 'CAPTURED'];

    public function __construct( protected string  $gateway, protected string  $mode)
    {
        $this->mid             = config("larapay.{$gateway}.mid");
        $this->api_key         = config("larapay.{$gateway}.{$mode}.api_key");
        $this->secret_key      = config("larapay.{$gateway}.{$mode}.secret_key");
        $this->currency        = Str::upper(config('larapay.currency', 'EGP'));
        $this->allowed_methods = config("larapay.{$gateway}.allowed_methods", 'card,wallet,bank_installments');
        $this->display         = config("larapay.{$gateway}.display", 'en');
        $this->endpoint        = $this->apiBase();

        $this->server_callback = config("larapay.{$gateway}.server_callback") ?: route('larapay.server-callback', $gateway);
        $this->client_callback = config("larapay.{$gateway}.client_callback") ?: route('larapay.client-callback', $gateway);

        parent::__construct();
    }

    // =========================================================================
    // Interface
    // =========================================================================

    public function init(): static 
    { 
        if (empty($this->mid) || empty($this->api_key)) {
            throw new \Larapay\Core\Exceptions\GatewayConfigurationException("Kashier mid or api_key is missing in configuration.");
        }
        return $this; 
    }

    public function set(
        ?string $uid                        = null,
        ?string $currency                   = null,
        ?float  $amount                     = null,
        mixed   $cart_id                    = null,
        ?string $cart_description           = null,
        ?string $server_callback            = null,
        ?string $client_callback            = null,
        ?string $refrance                   = null,
        ?string $allowed_methods            = null,
        ?string $display                    = null,
        ?LarapayTransaction $transaction    = null,
        ?string $reason                     = null,
    ): static {
        $this->uid              = $uid              ?? $this->uid;
        $this->currency         = $currency         ? Str::upper($currency) : $this->currency;
        $this->amount           = $amount           ?? $this->amount;
        $this->cart_id          = $cart_id          ?? $this->cart_id;
        $this->cart_description = $cart_description ?? $this->cart_description;
        $this->server_callback  = $server_callback  ?? $this->server_callback;
        $this->client_callback  = $client_callback  ?? $this->client_callback;
        $this->refrance         = $refrance         ?? $this->refrance;
        $this->allowed_methods  = $allowed_methods  ?? $this->allowed_methods;
        $this->display          = $display          ?? $this->display;
        $this->transaction      = $transaction      ?? $this->transaction;
        $this->reason           = $reason           ?? $this->reason;
        return $this;
    }

    // =========================================================================
    // Mode A — HPP Redirect
    // =========================================================================

    /**
     * Build the Kashier Hosted Payment Page redirect URL (no API call).
     */
    public function pay(?float $amount = null): static
    {
        if ($this->hasError()) return $this;

        $this->amount = $amount ?? $this->amount;

        if (! $this->mid || ! $this->api_key) {
            $this->error = __('Kashier MID or API key is not configured.');
            return $this;
        }
        if (! $this->amount || ! $this->currency) {
            $this->error = __('Amount and currency are required for Kashier payment.');
            return $this;
        }

        $orderId = $this->cart_id ?? $this->uid;
        $amount  = number_format((float) $this->amount, 2, '.', '');

        $this->redirect = self::BASE_URL . '?' . http_build_query([
            'merchantId'       => $this->mid,
            'orderId'          => $orderId,
            'amount'           => $amount,
            'currency'         => $this->currency,
            'hash'             => $this->generateHash($orderId, $amount, $this->currency),
            'mode'             => $this->modeParam(),
            'merchantRedirect' => $this->client_callback,
            'serverWebhook'    => $this->server_callback,
            'allowedMethods'   => $this->allowed_methods,
            'display'          => $this->display,
        ]);

        return $this;
    }

    // =========================================================================
    // Mode B — API / Custom Form
    // =========================================================================

    /**
     * Step 1 — Create a Kashier Payment Session via the REST API.
     *
     * POST /v3/payment/sessions
     * Requires secret_key (Authorization) + api_key headers.
     */
    public function createSession($expireAt = null): static
    {
        if ($this->hasError()) return $this;

        if (! $this->mid || ! $this->api_key || ! $this->secret_key) {
            $this->error = __('Kashier MID, API key, and Secret key are all required for session-based payments.');
            return $this;
        }
        if (! $this->amount || ! $this->currency) {
            $this->error = __('Amount and currency are required.');
            return $this;
        }

        $expireAt = $expireAt ?? now()->addHour()->toIso8601String();
        $orderId  = $this->uid;
        $amount   = number_format((float) $this->amount, 2, '.', '');

        $body = [
            'merchantId'         => $this->mid,
            'amount'             => $amount,
            'currency'           => $this->currency,
            'order'              => $orderId,
            'expireAt'           => $expireAt,
            'maxFailureAttempts' => 3,
            'type'               => 'one-time',
            'allowedMethods'     => $this->allowed_methods,
            'display'            => $this->display,
            'merchantRedirect'   => $this->client_callback,
            'serverWebhook'      => $this->server_callback,
            'failureRedirect'    => false,
            'enable3DS'          => true,
            'interactionSource'  => 'ECOMMERCE',
        ];

        if ($this->cart_description) {
            $body['description'] = Str::limit($this->cart_description, 120);
        }

        if (! empty($this->billing['email'])) {
            $body['customer'] = [
                'email'     => $this->billing['email'],
                'reference' => $orderId,
            ];
        }
        
        $this->post($this->apiBase() . '/v3/payment/sessions', $body, $this->apiHeaders());

        if ($this->hasError()) return $this;

        $json = $this->json();

        if (empty($json->_id)) {
            $this->error = __('Kashier did not return a session ID.');
            return $this;
        }

        $this->session_id  = $json->_id;
        $this->session_url = $json->sessionUrl ?? null;

        return $this;
    }

    /**
     * Step 2 — Redirect user to the Kashier-hosted session payment page.
     *
     * Must call createSession() first.
     * Kashier handles card entry on their secure hosted page,
     * then redirects back to your merchantRedirect URL.
     */
    public function getSessionRedirect(): static
    {
        if (! $this->session_url) {
            $this->error = __('No Kashier session URL. Call createSession() first.');
            return $this;
        }
        $this->redirect = $this->session_url;
        return $this;
    }

    /**
     * Return an order-review Blade page with a "Pay Now" button.
     *
     * Shows order summary and redirects the user to the Kashier session URL
     * when they click Pay. Must call createSession() first.
     */
    public function getPayForm(?string $storeName = null): View
    {
        if (! $this->session_id || ! $this->session_url) {
            abort(500, 'Call createSession() before getPayForm().');
        }

        return view('larapay::gateways.kashier.form', [
            'sessionId'   => $this->session_id,
            'sessionUrl'  => $this->session_url,
            'orderId'     => $this->uid,
            'amount'      => number_format((float) ($this->amount ?? 0), 2, '.', ''),
            'currency'    => $this->currency,
            'description' => $this->cart_description,
            'mode'        => $this->modeParam(),
            'display'     => $this->display,
            'storeName'   => $storeName ?? config('app.name', 'Store'),
        ]);
    }

    /**
     * Step 4 — Fetch session status (for polling or webhook verification).
     *
     * GET /v3/payment/sessions/{sessionId}/payment
     */
    public function checkSession(?string $sessionId = null): static
    {
        if ($this->hasError()) return $this;

        $sid = $sessionId ?? $this->session_id ?? $this->refrance;
        if (! $sid) {
            $this->error = __('No Kashier session ID to check.');
            return $this;
        }

        $this->get(
            $this->apiBase() . "/v3/payment/sessions/{$sid}/payment",
            [],
            ['Authorization' => $this->secret_key]
        );

        if (! $this->hasError()) {
            $json = $this->json();
            $this->callbackData = (array) ($json->data ?? $json);
            $this->refrance     = $this->callbackData['orderId']
                               ?? $this->callbackData['sessionId']
                               ?? $sid;
        }

        return $this;
    }

    /**
     * GET /v2/aggregator/transactions/{transactionID}
     */
    public function check(): static
    {
        if ($this->hasError()) return $this;

        if (!$this->transaction) {
            $this->error = __('Set transaction before check it.');
            return $this;
        }

        if (!$this->transaction->refrance) {
            return $this->checkByOrderID();
        }

        return $this->runCheck();
    }

    private function runCheck(): static
    {
        $this->get(
            $this->apiBase() . "/v2/aggregator/transactions/{$this->transaction->refrance}",
            [],
            ['Authorization' => $this->secret_key]
        );
        //$this->callbackData = (array) ($this->json() ?? []);
        $this->updateTransaction();
        return $this;
    }

    /**
     * GET /v2/aggregator/transactions?search=orderID
     */
    public function checkByOrderID(): static
    {
        $this->get(
            $this->apiBase() . "/v2/aggregator/transactions",
            ['search' => $this->transaction->uid],
            ['Authorization' => $this->secret_key]
        );
        $json = $this->json()->body[0] ?? false;
        if(!$json || $json->merchantOrderId != $this->transaction->uid){
            $this->error = __('Transaction not found.');
            return $this;
        }
        $this->transaction->refrance = $json->transactionId;
        $this->transaction->save();
        return $this->runCheck();
    }

    /**
     * Update transaction in database after check it from gatway
     */
    private function updateTransaction(): void
    {
        $status = $this->json()->body->paymentStatus ?? false;
        if($status && $this->paymentAccepted()){
            $this->transaction->status = 'success';
        }else{
            $this->transaction->status = 'cancelled';
        }
        $this->transaction->response = json_encode($this->json());
        $this->transaction->save();
    }

    // =========================================================================
    // Mode C — Refund
    // =========================================================================

    /**
     * Refund a Kashier transaction (full or partial).
     *
     * PUT /v3/orders/{orderId}
     *
     * @param  float        $amount  Amount to refund (decimal, e.g. 50.00)
     * @param  string|null  $reason  Optional reason shown in Kashier dashboard
     *
     * Requires the transaction's uid (the orderId sent to Kashier) to be set
     * via ->set(uid: $transaction->uid) or ->set(refrance: $transaction->uid).
     */
    public function refund(?float $amount = null, ?string $reason = null): static
    {
        $this->amount = $amount ?? $this->amount;
        $this->reason = $reason ?? $this->reason;
        
        if ($this->hasError()) return $this;

        if (! $this->secret_key) {
            $this->error = __('Kashier Secret key is required for refunds.');
            return $this;
        }
        if (! $this->amount || $this->amount <= 0) {
            $this->error = __('A positive refund amount is required.');
            return $this;
        }

        // orderId in the URL = the uid we originally sent to Kashier as the order reference
        $orderId =  $this->transaction->uid ?? $this->transaction->refrance;

        if (! $orderId) {
            $this->error = __('Kashier orderId (uid) is required for refunds.');
            return $this;
        }

        $url  = $this->refundBase() . "/v3/orders/{$orderId}";
        $body = [
            'apiOperation' => 'REFUND',
            'reason'       => $this->reason ?? 'Customer refund request',
            'transaction'  => [
                'amount' => $this->amount,
            ],
        ];

        $this->put($url, $body, [
            'Authorization' => $this->secret_key,
            'Content-Type'  => 'application/json',
            'accept'        => 'application/json',
        ]);

        if (! $this->hasError()) {
            $json = $this->json();
            $this->callbackData = (array) ($json ?? []);
            $this->registerRefund();
        }

        return $this;
    }

    // =========================================================================
    // HPP callback signature verification (Mode A redirect return)
    // =========================================================================

    /**
     * Verify the HMAC Kashier appends to the redirect-back URL.
     * All query params except "signature" and "mode" are signed.
     */
    public function check2(?array $queryParams = null): static
    {
        if ($this->hasError()) return $this;

        $queryParams = $queryParams ?? request()->query();

        if (empty($queryParams['signature'])) {
            $this->error = __('Missing Kashier callback signature.');
            return $this;
        }

        $received = $queryParams['signature'];
        $parts    = [];

        foreach ($queryParams as $key => $value) {
            if ($key === 'signature' || $key === 'mode') continue;
            $parts[] = "{$key}={$value}";
        }

        $expected = hash_hmac('sha256', implode('&', $parts), $this->api_key, false);

        if (! hash_equals($expected, $received)) {
            $this->error = __('Kashier callback signature verification failed.');
            return $this;
        }

        $this->refrance     = $queryParams['transactionId'] ?? $queryParams['orderId'] ?? $this->refrance;
        $this->callbackData = $queryParams;

        return $this;
    }

    // =========================================================================
    // Status helpers
    // =========================================================================

    public function paymentAccepted(): bool
    {
        $status = $this->json()->body->paymentStatus ?? 'UNKNOWN';
        return in_array(strtoupper((string) $status), $this->acceptedSuccessStatus);
    }

    public function paymentCancelled(): bool
    {
        $status = $this->callbackData['paymentStatus'] ?? $this->callbackData['status'] ?? $this->json()->body->status ?? null;
        return in_array(strtoupper((string) $status), ['CANCELLED', 'CANCEL', 'FAILED', 'FAILURE', 'DECLINED']);
    }

    /**
     * Check if the refund was successful.
     * Refund response uses top-level "status": "SUCCESS".
     */
    public function refundAccepted(): bool
    {
        $status = $this->json()->response->result ?? 'UNKNOWN';
        return strtoupper((string) $status) === 'SUCCESS';
    }

    public function hasTocken(): bool        { return false; }
    public function getSessionId(): ?string  { return $this->session_id; }
    public function getSessionUrl(): ?string { return $this->session_url; }

    public function setSessionId(string $id): static
    {
        $this->session_id = $id;
        return $this;
    }

    // =========================================================================
    // Transaction persistence
    // =========================================================================

    public function register(): void
    {
        $tx           = new LarapayTransaction;
        $tx->type     = 'sale';
        $tx->uid      = $this->cart_id ?? $this->uid;
        $tx->gateway  = $this->gateway;
        $tx->refrance = $this->refrance ?? null;
        $tx->amount   = $this->amount   ?? 0;
        $tx->currency = $this->currency ?? null;
        $tx->response = json_encode($this->callbackData ?? null);
        $tx->status   = 'pending';
        $tx->save();
    }

    public function registerRefund(): void
    {
        if(!$this->transaction){
            $this->error = __('Set transaction before make a refund.');
            return;
        }
        $tx            = new LarapayTransaction;
        $tx->type      = 'refund';
        $tx->uid       = $this->json()->response->transactionId ?? uniqid();
        $tx->gateway   = $this->gateway;
        $tx->refrance  = $this->json()->response->transactionId ?? null;
        $tx->amount    = $this->amount   ?? 0;
        $tx->currency  = $this->transaction->currency ?? null;
        $tx->response  = json_encode($this->callbackData ?? []);
        $tx->status    = 'success';
        $tx->parent_id = $this->transaction->id;

        if (! LarapayTransaction::where('refrance', $tx->refrance)
                                 ->where('gateway',  $tx->gateway)->first()) {
            $tx->save();
        }
    }

    public function json(): object|null
    {
        // After an HTTP call, always return the raw response object
        if (isset($this->response)) {
            return parent::json();
        }
        // Fallback to stored callback data (HPP redirect flow)
        return $this->callbackData ? (object) $this->callbackData : null;
    }

    /**
     * Safely return the raw HTTP response body (null if no request made yet).
     */
    public function responseBody(): ?string
    {
        return isset($this->response) ? $this->response->body() : null;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function apiBase(): string
    {
        return $this->mode === 'live' ? self::API_LIVE : self::API_TEST;
    }

    protected function refundBase(): string
    {
        return $this->mode === 'live' ? self::REFUND_LIVE : self::REFUND_TEST;
    }

    protected function modeParam(): string
    {
        return $this->mode === 'live' ? 'live' : 'test';
    }

    protected function apiHeaders(): array
    {
        return [
            'Authorization' => $this->secret_key,
            'api-key'       => $this->api_key,
            'Content-Type'  => 'application/json',
        ];
    }

    protected function generateHash(mixed $orderId, string $amount, string $currency): string
    {
        $path = "/?payment={$this->mid}.{$orderId}.{$amount}.{$currency}";
        return hash_hmac('sha256', $path, $this->api_key, false);
    }

    public function enableWallet(): static
    {
        if (! in_array('wallet', $this->allowed_methods_arr)) $this->allowed_methods_arr[] = 'wallet';
        $this->allowed_methods = implode(',', $this->allowed_methods_arr);
        return $this;
    }

    public function enableCard(): static
    {
        if (! in_array('card', $this->allowed_methods_arr)) $this->allowed_methods_arr[] = 'card';
        $this->allowed_methods = implode(',', $this->allowed_methods_arr);
        return $this;
    }

    public function enableInstallments(): static
    {
        if (! in_array('bank_installments', $this->allowed_methods_arr)) $this->allowed_methods_arr[] = 'bank_installments';
        $this->allowed_methods = implode(',', $this->allowed_methods_arr);
        return $this;
    }
}

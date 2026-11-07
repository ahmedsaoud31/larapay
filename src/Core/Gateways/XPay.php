<?php

namespace Larapay\Core\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Larapay\Core\Exceptions\GatewayConfigurationException;
use Larapay\Core\Exceptions\GatewayConnectionException;
use Larapay\Core\Gateways\XPay\Traits\Billing;
use Larapay\Core\Gateways\XPay\Traits\Cart;
use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;
use Larapay\Models\LarapayTransaction;

/**
 * XPay (xpay.app) payment gateway.
 *
 * Flow:
 *   1. pay()     POST /api/v1/payments/pay/variable-amount  → iframe_url + transaction_uuid
 *                The customer is redirected to iframe_url (getRedirect()).
 *   2. XPay redirects the customer to the "Redirect URL" and POSTs the
 *      "Callback URL" configured in the XPay dashboard (per API Payment).
 *   3. check()   GET  /api/v1/communities/{community_id}/transactions/{uuid}/
 *                Always use this to verify status server-side.
 *   4. refund()  POST /api/payments/refund-transaction/  (full or partial)
 *
 * Config keys (config/larapay.php → 'xpay'):
 *   community_id        – Community (merchant) ID
 *   variable_amount_id  – ID of the "API Payment" created in the XPay dashboard
 *   live.api_key        – Live x-api-key
 *   sandbox.api_key     – Staging x-api-key
 *   pay_using           – card | fawry | kiosk | meeza/digital | valu | apple
 *   language            – en | ar
 *   add_fees            – true to add XPay service fees on top (prepare-amount)
 *
 * @see https://xpayeg.github.io/docs/
 */
class XPay extends LarapayBase implements LarapayInterface
{
    use Cart, Billing;

    protected const HOST_LIVE = 'https://community.xpay.app';
    protected const HOST_TEST = 'https://staging.xpay.app';

    protected ?string $community_id       = null;
    protected ?string $api_key            = null;
    protected ?int    $variable_amount_id = null;
    protected ?string $pay_using          = 'card';
    protected ?string $language           = 'en';
    protected bool    $add_fees           = false;
    protected ?string $endpoint           = null;

    protected ?float  $amount          = null;
    protected ?float  $total_amount    = null;
    protected ?string $currency        = null;
    protected ?string $server_callback = null;
    protected ?string $client_callback = null;
    protected ?string $refrance        = null;
    protected ?string $reason          = null;
    protected ?string $member_id       = null;
    protected array   $custom_fields   = [];
    protected ?LarapayTransaction $transaction = null;

    protected ?string $iframe_url     = null;
    protected ?int    $transaction_id = null;
    protected array   $callbackData   = [];
    protected bool    $refundRegistered = false;

    protected array $acceptedSuccessStatus = ['SUCCESSFUL', 'SUCCESS', 'PAID', 'CAPTURED'];
    protected array $cancelledStatus       = ['FAILED', 'FAILURE', 'CANCELLED', 'CANCELED', 'DECLINED', 'EXPIRED', 'VOIDED'];

    public function __construct(protected string $gateway, protected string $mode)
    {
        $this->mode               = $mode === 'live' ? 'live' : 'sandbox';
        $this->community_id       = config("larapay.{$gateway}.community_id");
        $this->api_key            = config("larapay.{$gateway}.{$this->mode}.api_key");
        $vaId                     = config("larapay.{$gateway}.variable_amount_id");
        $this->variable_amount_id = is_numeric($vaId) ? (int) $vaId : null;
        $this->pay_using          = config("larapay.{$gateway}.pay_using", 'card') ?: 'card';
        $this->language           = config("larapay.{$gateway}.language", 'en') ?: 'en';
        $this->add_fees           = (bool) config("larapay.{$gateway}.add_fees", false);
        $this->currency           = Str::upper(config("larapay.{$gateway}.currency") ?: config('larapay.currency', 'EGP'));
        $this->endpoint           = $this->host() . '/api/v1/';

        $this->server_callback = config("larapay.{$gateway}.server_callback") ?: route('larapay.server-callback', $gateway);
        $this->client_callback = config("larapay.{$gateway}.client_callback") ?: route('larapay.client-callback', $gateway);

        parent::__construct();
    }

    // =========================================================================
    // Interface
    // =========================================================================

    public function init(): static
    {
        if (empty($this->community_id) || empty($this->api_key)) {
            throw new GatewayConfigurationException("XPay community_id or {$this->mode} api_key is missing in configuration.");
        }
        return $this;
    }

    public function set(
        ?string $uid                     = null,
        ?string $currency                = null,
        ?float  $amount                  = null,
        mixed   $cart_id                 = null,
        ?string $cart_description        = null,
        ?string $server_callback         = null,
        ?string $client_callback         = null,
        ?string $refrance                = null,
        ?string $pay_using               = null,
        ?string $language                = null,
        ?string $member_id               = null,
        ?int    $variable_amount_id      = null,
        ?LarapayTransaction $transaction = null,
        ?string $reason                  = null,
    ): static {
        $this->uid                = $uid                ?? $this->uid;
        $this->currency           = $currency           ? Str::upper($currency) : $this->currency;
        $this->amount             = $amount             ?? $this->amount;
        $this->cart_id            = $cart_id            ?? $this->cart_id;
        $this->cart_description   = $cart_description   ?? $this->cart_description;
        $this->server_callback    = $server_callback    ?? $this->server_callback;
        $this->client_callback    = $client_callback    ?? $this->client_callback;
        $this->refrance           = $refrance           ?? $this->refrance;
        $this->pay_using          = $pay_using          ?? $this->pay_using;
        $this->language           = $language           ?? $this->language;
        $this->member_id          = $member_id          ?? $this->member_id;
        $this->variable_amount_id = $variable_amount_id ?? $this->variable_amount_id;
        $this->transaction        = $transaction        ?? $this->transaction;
        $this->reason             = $reason             ?? $this->reason;
        return $this;
    }

    /**
     * Extra data saved with the XPay invoice (shown in the XPay dashboard
     * and echoed back in the callback body).
     *
     * @param array<string, string|int|float|bool> $fields label => value
     */
    public function customFields(array $fields): static
    {
        foreach ($fields as $label => $value) {
            $this->custom_fields[] = ['field_label' => (string) $label, 'field_value' => $value];
        }
        return $this;
    }

    // =========================================================================
    // Prepare amount (service fees)
    // =========================================================================

    /**
     * POST /api/v1/payments/prepare-amount/
     * Returns the total amount including XPay service fees (or null on error).
     */
    public function prepareAmount(?float $amount = null): ?float
    {
        $amount = $amount ?? $this->amount;

        $this->request('post', $this->endpoint . 'payments/prepare-amount/', [
            'community_id'            => $this->community_id,
            'amount'                  => round((float) $amount, 2),
            'currency'                => $this->currency,
            'selected_payment_method' => $this->pay_using,
        ]);

        if ($this->hasError()) return null;

        $total = $this->json()->data->total_amount ?? null;
        if (! is_numeric($total)) {
            $this->error = __('XPay did not return a total amount.');
            return null;
        }

        return $this->total_amount = round((float) $total, 2);
    }

    // =========================================================================
    // Pay
    // =========================================================================

    /**
     * POST /api/v1/payments/pay/variable-amount
     * On success, getRedirect() returns the XPay hosted payment page (iframe_url)
     * and getTransactionUuid() the XPay transaction UUID.
     */
    public function pay(?float $amount = null): static
    {
        if ($this->hasError()) return $this;

        $this->amount = $amount ?? $this->amount;

        if (! $this->variable_amount_id) {
            $this->error = __('XPay API Payment ID (variable_amount_id) is not configured.');
            return $this;
        }
        if (! $this->amount || $this->amount <= 0 || ! $this->currency) {
            $this->error = __('Amount and currency are required for XPay payment.');
            return $this;
        }
        if (empty($this->billing['email']) || empty($this->billing['phone'])) {
            $this->error = __('Customer email and phone number are required for XPay payment.');
            return $this;
        }

        $original = round((float) $this->amount, 2);
        $total    = $original;

        if ($this->add_fees) {
            $total = $this->prepareAmount($original);
            if ($this->hasError()) return $this;
        }
        $this->total_amount = $total;

        $body = [
            'community_id'       => $this->community_id,
            'variable_amount_id' => $this->variable_amount_id,
            'amount'             => $total,
            'original_amount'    => $original,
            'currency'           => $this->currency,
            'pay_using'          => $this->pay_using,
            'language'           => $this->language,
            'billing_data'       => $this->billingPayload(),
        ];

        if ($this->member_id) {
            $body['membership_id'] = $this->member_id;
        }

        $fields = $this->custom_fields;
        if ($this->cart_id) {
            array_unshift($fields, ['field_label' => 'order_id', 'field_value' => (string) $this->cart_id]);
        }
        if ($fields) {
            $body['custom_fields'] = $fields;
        }

        $this->request('post', $this->endpoint . 'payments/pay/variable-amount', $body);

        if ($this->hasError()) return $this;

        $data = $this->json()->data ?? null;

        if (empty($data->transaction_uuid)) {
            $this->error = __('XPay did not return a transaction reference.');
            return $this;
        }

        $this->callbackData   = (array) $data;
        $this->refrance       = $data->transaction_uuid;
        $this->transaction_id = isset($data->transaction_id) ? (int) $data->transaction_id : null;
        $this->iframe_url     = $data->iframe_url ?? null;

        if ($this->iframe_url) {
            $this->redirect = $this->iframe_url;
        }

        return $this;
    }

    // =========================================================================
    // Inquiry
    // =========================================================================

    /**
     * GET /api/v1/communities/{community_id}/transactions/{uuid}/
     *
     * Uses the set LarapayTransaction's refrance (and updates its status),
     * or a plain refrance set via ->set(refrance: $uuid).
     */
    public function check(?string $transactionUuid = null): static
    {
        if ($this->hasError()) return $this;

        $uuid = $transactionUuid ?? $this->transaction?->refrance ?? $this->refrance;

        if (! $uuid) {
            $this->error = __('No XPay transaction UUID to check.');
            return $this;
        }

        $this->request(
            'get',
            $this->endpoint . "communities/{$this->community_id}/transactions/{$uuid}/"
        );

        if ($this->hasError()) return $this;

        $this->callbackData = (array) ($this->json()->data ?? []);
        $this->refrance     = $uuid;

        if ($this->transaction) {
            $this->updateTransaction();
        }

        return $this;
    }

    /**
     * Update the stored transaction after inquiry.
     */
    private function updateTransaction(): void
    {
        if ($this->paymentAccepted()) {
            $this->transaction->status = 'success';
        } elseif ($this->paymentCancelled()) {
            $this->transaction->status = 'cancelled';
        }
        $this->transaction->response = json_encode($this->callbackData);
        $this->transaction->save();
    }

    // =========================================================================
    // Refund
    // =========================================================================

    /**
     * POST /api/payments/refund-transaction/  (note: no /v1 in this path)
     * Partial refund if amount < paid amount, full refund otherwise.
     */
    public function refund(?float $amount = null, ?string $reason = null): static
    {
        $this->amount = $amount ?? $this->amount;
        $this->reason = $reason ?? $this->reason;

        if ($this->hasError()) return $this;

        $uuid = $this->transaction?->refrance ?? $this->refrance;

        if (! $uuid) {
            $this->error = __('XPay transaction UUID is required for refunds.');
            return $this;
        }
        if (! $this->amount || $this->amount <= 0) {
            $this->error = __('A positive refund amount is required.');
            return $this;
        }

        $this->request('post', $this->host() . '/api/payments/refund-transaction/', [
            'transaction_uuid' => $uuid,
            'refund_amount'    => round((float) $this->amount, 2),
        ]);

        if ($this->hasError()) return $this;

        $this->callbackData = (array) ($this->json()->data ?? []);
        $this->refrance     = $uuid;

        if ($this->transaction) {
            $this->registerRefund();
        }

        return $this;
    }

    // =========================================================================
    // Status helpers
    // =========================================================================

    public function getStatus(): ?string
    {
        $status = $this->callbackData['status'] ?? $this->callbackData['transaction_status'] ?? null;
        return $status ? strtoupper((string) $status) : null;
    }

    public function paymentAccepted(): bool
    {
        return in_array($this->getStatus(), $this->acceptedSuccessStatus, true);
    }

    public function paymentCancelled(): bool
    {
        return in_array($this->getStatus(), $this->cancelledStatus, true);
    }

    public function paymentPending(): bool
    {
        return ! $this->paymentAccepted() && ! $this->paymentCancelled();
    }

    public function refundAccepted(): bool
    {
        if (! isset($this->response) || ! $this->response->successful()) return false;
        $code = (int) ($this->json()->status->code ?? $this->response->status());
        return $code >= 200 && $code < 300;
    }

    /** Amount reported by XPay for the checked transaction. */
    public function getPaidAmount(): ?float
    {
        $amount = $this->callbackData['total_amount'] ?? null;
        return is_numeric($amount) ? (float) $amount : null;
    }

    /** Currency reported by XPay for the checked transaction. */
    public function getPaidCurrency(): ?string
    {
        $currency = $this->callbackData['total_amount_currency'] ?? null;
        return $currency ? strtoupper((string) $currency) : null;
    }

    public function getTransactionUuid(): ?string { return $this->refrance; }
    public function getTransactionId(): ?int      { return $this->transaction_id; }
    public function getIframeUrl(): ?string       { return $this->iframe_url; }
    public function getTotalAmount(): ?float      { return $this->total_amount; }
    public function getData(): array              { return $this->callbackData; }
    public function hasTocken(): bool             { return false; }

    // =========================================================================
    // Transaction persistence
    // =========================================================================

    /**
     * Store the initiated payment as a pending LarapayTransaction.
     */
    public function register(): LarapayTransaction
    {
        $tx           = new LarapayTransaction;
        $tx->type     = 'sale';
        $tx->mode     = $this->mode;
        $tx->uid      = $this->cart_id ?? $this->uid;
        $tx->gateway  = $this->gateway;
        $tx->refrance = $this->refrance;
        $tx->amount   = $this->total_amount ?? $this->amount ?? 0;
        $tx->currency = $this->currency;
        $tx->response = json_encode($this->callbackData ?: null);
        $tx->status   = 'pending';
        $tx->save();

        $this->transaction = $tx;

        return $tx;
    }

    public function registerRefund(?LarapayTransaction $parent = null): void
    {
        $parent = $parent ?? $this->transaction;

        if (! $parent) {
            $this->error = __('Set transaction before make a refund.');
            return;
        }

        // Avoid double registration when called by both refund() and the caller.
        if ($this->refundRegistered) return;
        $this->refundRegistered = true;

        $tx            = new LarapayTransaction;
        $tx->type      = 'refund';
        $tx->mode      = $parent->mode ?? $this->mode;
        $tx->uid       = $parent->uid;
        $tx->gateway   = $this->gateway;
        $tx->refrance  = $parent->refrance . '-refund-' . uniqid();
        $tx->amount    = $this->amount ?? 0;
        $tx->currency  = $parent->currency;
        $tx->response  = json_encode($this->callbackData ?: []);
        $tx->status    = 'success';
        $tx->parent_id = $parent->id;
        $tx->save();
    }

    // =========================================================================
    // HTTP
    // =========================================================================

    /**
     * Send a request with the x-api-key header and parse XPay's error envelope:
     *   { "status": { "code": 400, "message": "", "errors": [ {...} ] }, "data": {} }
     */
    protected function request(string $method, string $url, array $data = []): void
    {
        $this->error  = false;
        $this->errors = [];

        $headers = [
            'x-api-key'    => $this->api_key,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (config('larapay.debug')) {
            Log::info('[Larapay][XPay] ' . strtoupper($method) . " {$url}", ['data' => $data]);
        }

        try {
            $client = Http::withHeaders($headers)->timeout(30);
            $this->response = $method === 'get'
                ? $client->get($url, $data)
                : $client->{$method}($url, $data);
        } catch (\Throwable $e) {
            if (config('larapay.debug')) {
                Log::error('[Larapay][XPay] ' . strtoupper($method) . " {$url} Error", ['error' => $e->getMessage()]);
            }
            $ex = new GatewayConnectionException($this->gateway);
            $this->error = $ex->getMessage();
            throw $ex;
        }

        if (config('larapay.debug')) {
            Log::info('[Larapay][XPay] Response', ['status' => $this->response->status(), 'body' => $this->response->body()]);
        }

        $json = $this->response->object();
        $code = (int) ($json->status->code ?? $this->response->status());

        if ($this->response->successful() && $code >= 200 && $code < 300) {
            return;
        }

        foreach ((array) ($json->status->errors ?? []) as $err) {
            $this->collectErrors($err);
        }

        $message = trim((string) ($json->status->message ?? ''));
        $this->error = $message !== ''
            ? $message
            : ($this->errors[0] ?? "{$this->response->status()}: {$this->response->reason()}");
    }

    private function collectErrors(mixed $err, string $prefix = ''): void
    {
        if (is_string($err)) {
            $this->errors[] = $prefix ? "{$prefix}: {$err}" : $err;
            return;
        }
        foreach ((array) $err as $field => $value) {
            $label = is_int($field) ? $prefix : ($prefix ? "{$prefix}.{$field}" : $field);
            if (is_array($value) && array_is_list($value) && isset($value[0]) && is_string($value[0])) {
                $this->errors[] = "{$label}: {$value[0]}";
            } else {
                $this->collectErrors($value, $label);
            }
        }
    }

    public function json(): object|null
    {
        if (isset($this->response)) {
            return $this->response->object();
        }
        return $this->callbackData ? (object) $this->callbackData : null;
    }

    public function responseBody(): ?string
    {
        return isset($this->response) ? $this->response->body() : null;
    }

    protected function host(): string
    {
        return $this->mode === 'live' ? self::HOST_LIVE : self::HOST_TEST;
    }
}

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
 * XPay (xpay.app) payment gateway driver (Modern Checkout Sessions & Refunds API).
 *
 * Flow:
 *   1. pay()     POST https://api.xpay.app/checkout/sessions
 *                Returns Checkout Session (cs_test_... / cs_live_...) and hosted checkout `url`.
 *                Customer is redirected to `getRedirect()`.
 *   2. Return    XPay replaces `{CHECKOUT_SESSION_ID}` in `afterCompletion.redirect.url`
 *                and redirects the customer browser back, plus sends signed webhooks
 *                (`checkout.session.completed`, `checkout.session.async_payment_succeeded`).
 *   3. check()   GET  https://api.xpay.app/checkout/sessions/{session_id}
 *                Verifies `paymentStatus === 'paid'` and `status === 'complete'` server-side.
 *   4. refund()  POST https://api.xpay.app/refunds
 *                Full or partial refund in minor units using `paymentIntentId` (`pi_...`) or `chargeId` (`ch_...`).
 *
 * Config keys (config/larapay.php → 'xpay'):
 *   live.publishable_key    – pk_live_...
 *   live.secret_key         – sk_live_...
 *   live.webhook_secret     – whsec_...
 *   sandbox.publishable_key – pk_test_...
 *   sandbox.secret_key      – sk_test_...
 *   sandbox.webhook_secret  – whsec_...
 *   currency                – USD | EGP | EUR | GBP | SAR | AED (default USD)
 *
 * @see https://docs.xpay.app/en
 */
class XPay extends LarapayBase implements LarapayInterface
{
    use Cart, Billing;

    protected const BASE_URL = 'https://api.xpay.app';

    protected const ALLOWED_REFUND_REASONS = [
        'requested_by_customer',
        'duplicate',
        'fraudulent',
        'expired_uncaptured_charge',
    ];

    protected ?string $publishable_key = null;
    protected ?string $secret_key      = null;
    protected ?string $webhook_secret  = null;
    protected ?string $endpoint        = null;

    protected ?float  $amount            = null;
    protected ?float  $total_amount      = null;
    protected ?string $currency          = null;
    protected ?string $server_callback   = null;
    protected ?string $client_callback   = null;
    protected ?string $cancel_url        = null;
    protected ?string $refrance          = null;
    protected ?string $payment_intent_id = null;
    protected ?string $charge_id         = null;
    protected ?string $refund_id         = null;
    protected ?string $reason            = null;
    protected array   $metadata          = [];
    protected ?LarapayTransaction $transaction = null;

    protected ?string $iframe_url       = null;
    protected array   $callbackData     = [];
    protected bool    $refundRegistered = false;

    public function __construct(protected string $gateway, protected string $mode)
    {
        $this->mode            = $mode === 'live' ? 'live' : 'sandbox';
        $this->publishable_key = config("larapay.{$gateway}.{$this->mode}.publishable_key");
        $this->secret_key      = config("larapay.{$gateway}.{$this->mode}.secret_key")
                              ?: config("larapay.{$gateway}.{$this->mode}.api_key");
        $this->webhook_secret  = config("larapay.{$gateway}.{$this->mode}.webhook_secret");
        $this->currency        = Str::upper(config("larapay.{$gateway}.currency") ?: config('larapay.currency', 'USD'));
        $this->endpoint        = rtrim(self::BASE_URL, '/') . '/';

        $this->server_callback = config("larapay.{$gateway}.server_callback") ?: route('larapay.server-callback', $gateway);
        $this->client_callback = config("larapay.{$gateway}.client_callback") ?: route('larapay.client-callback', $gateway);

        parent::__construct();
    }

    // =========================================================================
    // Interface & Configuration
    // =========================================================================

    public function init(): static
    {
        if (empty($this->secret_key)) {
            throw new GatewayConfigurationException("XPay {$this->mode} secret_key (sk_...) is missing in configuration.");
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
        ?string $cancel_url              = null,
        ?string $refrance                = null,
        ?string $payment_intent_id       = null,
        ?string $charge_id               = null,
        ?LarapayTransaction $transaction = null,
        ?string $reason                  = null,
        array   $metadata                = [],
    ): static {
        $this->uid               = $uid               ?? $this->uid;
        $this->currency          = $currency          ? Str::upper($currency) : $this->currency;
        $this->amount            = $amount            ?? $this->amount;
        $this->cart_id           = $cart_id           ?? $this->cart_id;
        $this->cart_description  = $cart_description  ?? $this->cart_description;
        $this->server_callback   = $server_callback   ?? $this->server_callback;
        $this->client_callback   = $client_callback   ?? $this->client_callback;
        $this->cancel_url        = $cancel_url        ?? $this->cancel_url;
        $this->refrance          = $refrance          ?? $this->refrance;
        $this->payment_intent_id = $payment_intent_id ?? $this->payment_intent_id;
        $this->charge_id         = $charge_id         ?? $this->charge_id;
        $this->transaction       = $transaction       ?? $this->transaction;
        $this->reason            = $reason            ?? $this->reason;
        if ($metadata) {
            $this->metadata = array_merge($this->metadata, $metadata);
        }
        return $this;
    }

    /**
     * Attach custom metadata key-value pairs to the Checkout Session or Refund.
     *
     * @param array<string, scalar> $fields
     */
    public function customFields(array $fields): static
    {
        foreach ($fields as $key => $value) {
            if ($value !== null && $value !== '') {
                $this->metadata[(string) $key] = (string) $value;
            }
        }
        return $this;
    }

    // =========================================================================
    // Pay (Create Checkout Session)
    // =========================================================================

    /**
     * POST https://api.xpay.app/checkout/sessions
     * Creates a hosted Checkout Session.
     * On success, getRedirect() returns the XPay checkout URL (`https://checkout.xpay.app/c/cs_...`)
     * and getTransactionUuid() returns the session ID (`cs_test_...` / `cs_live_...`).
     */
    public function pay(?float $amount = null): static
    {
        if ($this->hasError()) return $this;

        $this->amount = $amount ?? $this->amount;

        if (! $this->amount || $this->amount <= 0 || ! $this->currency) {
            $this->error = __('Amount and currency are required for XPay payment.');
            return $this;
        }

        $this->total_amount = round((float) $this->amount, 2);
        $unitAmount         = $this->toMinorUnits($this->total_amount);

        $orderRef    = (string) ($this->cart_id ?? $this->uid ?? uniqid('order_'));
        $productName = trim((string) ($this->cart_description ?: "Order #{$orderRef}"));

        $returnUrl = (string) $this->client_callback;
        if ($returnUrl !== '' && ! str_contains($returnUrl, '{CHECKOUT_SESSION_ID}')) {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';
            $returnUrl .= $separator . 'session_id={CHECKOUT_SESSION_ID}';
        }

        $metadata = array_merge([
            'order_id'       => $orderRef,
            'customer_name'  => $this->billing['name'] ?? null,
            'customer_email' => $this->billing['email'] ?? null,
            'customer_phone' => $this->billing['phone'] ?? null,
        ], $this->metadata);

        $metadata = array_filter($metadata, fn ($v) => $v !== null && $v !== '');

        $body = [
            'afterCompletion' => [
                'type'     => 'redirect',
                'redirect' => [
                    'url' => $returnUrl,
                ],
            ],
            'lineItems' => [
                [
                    'priceData' => [
                        'currency'    => $this->currency,
                        'unitAmount'  => $unitAmount,
                        'productData' => [
                            'name'        => Str::limit($productName, 120, ''),
                            'description' => Str::limit($productName, 250, ''),
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
        ];

        if ($this->cancel_url) {
            $body['cancelUrl'] = $this->cancel_url;
        }

        if ($metadata) {
            $body['metadata'] = $metadata;
        }

        $this->request('post', $this->endpoint . 'checkout/sessions', $body);

        if ($this->hasError()) return $this;

        $json = $this->json();

        if (empty($json->id)) {
            $this->error = __('XPay did not return a checkout session ID.');
            return $this;
        }

        $this->callbackData      = json_decode(json_encode($json), true) ?: [];
        $this->refrance          = (string) $json->id;
        $this->payment_intent_id = $this->extractPaymentIntentId($this->callbackData);
        $this->charge_id         = $this->extractChargeId($this->callbackData);
        $this->iframe_url        = $json->url ?? null;

        if ($this->iframe_url) {
            $this->redirect = $this->iframe_url;
        }

        return $this;
    }

    // =========================================================================
    // Inquiry (Retrieve Checkout Session)
    // =========================================================================

    /**
     * GET https://api.xpay.app/checkout/sessions/{session_id}
     *
     * Retrieves the Checkout Session status and updates the linked LarapayTransaction.
     */
    public function check(?string $sessionId = null): static
    {
        if ($this->hasError()) return $this;

        $id = $sessionId ?? $this->transaction?->refrance ?? $this->refrance;

        if (! $id) {
            $this->error = __('No XPay session ID to check.');
            return $this;
        }

        $this->request('get', $this->endpoint . 'checkout/sessions/' . urlencode($id));

        if ($this->hasError()) return $this;

        $json                    = $this->json();
        $this->callbackData      = json_decode(json_encode($json), true) ?: [];
        $this->refrance          = (string) ($json->id ?? $id);
        $this->payment_intent_id = $this->extractPaymentIntentId($this->callbackData) ?? $this->payment_intent_id;
        $this->charge_id         = $this->extractChargeId($this->callbackData) ?? $this->charge_id;

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
    // Refund (POST /refunds)
    // =========================================================================

    /**
     * POST https://api.xpay.app/refunds
     * Supports full and partial refunds in minor units.
     * Automatically resolves `paymentIntentId` (`pi_...`) or `chargeId` (`ch_...`)
     * from the stored transaction or by querying the Checkout Session (`cs_...`).
     */
    public function refund(?float $amount = null, ?string $reason = null): static
    {
        $this->amount = $amount ?? $this->amount;
        $this->reason = $reason ?? $this->reason;

        if ($this->hasError()) return $this;

        if (! $this->amount || $this->amount <= 0) {
            $this->error = __('A positive refund amount is required.');
            return $this;
        }

        [$paymentIntentId, $chargeId] = $this->resolveRefundTargets();

        if (! $paymentIntentId && ! $chargeId) {
            $this->error = __('Could not determine XPay PaymentIntent ID (pi_...) or Charge ID (ch_...) for this transaction.');
            return $this;
        }

        $rawReason = trim((string) ($this->reason ?? ''));
        $enumReason = in_array($rawReason, self::ALLOWED_REFUND_REASONS, true)
            ? $rawReason
            : 'requested_by_customer';

        $body = [
            'amount' => $this->toMinorUnits((float) $this->amount),
            'reason' => $enumReason,
        ];

        if ($paymentIntentId) {
            $body['paymentIntentId'] = $paymentIntentId;
        } else {
            $body['chargeId'] = $chargeId;
        }

        if ($rawReason !== '' && ! in_array($rawReason, self::ALLOWED_REFUND_REASONS, true)) {
            $body['description'] = Str::limit($rawReason, 250, '');
        }

        $orderRef = (string) ($this->transaction?->uid ?? $this->cart_id ?? $this->uid ?? '');
        if ($orderRef !== '') {
            $body['metadata'] = ['order_id' => $orderRef];
        }

        $this->request('post', $this->endpoint . 'refunds', $body);

        if ($this->hasError()) return $this;

        $json               = $this->json();
        $this->callbackData = json_decode(json_encode($json), true) ?: [];
        $this->refund_id    = isset($json->id) ? (string) $json->id : null;

        if (! $this->refundAccepted()) {
            $status = $json->status ?? 'unknown';
            $this->error = __("XPay refund did not succeed (status: {$status}).");
            return $this;
        }

        if ($this->transaction) {
            $this->registerRefund();
        }

        return $this;
    }

    /**
     * Resolve `[paymentIntentId, chargeId]` from explicit properties, stored
     * transaction JSON, or a live `check()` call if only `cs_...` is known.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function resolveRefundTargets(): array
    {
        $piId = $this->payment_intent_id;
        $chId = $this->charge_id;

        $ref = (string) ($this->transaction?->refrance ?? $this->refrance ?? '');
        if (! $piId && str_starts_with($ref, 'pi_')) {
            $piId = $ref;
        }
        if (! $chId && str_starts_with($ref, 'ch_')) {
            $chId = $ref;
        }

        if (! $piId && ! $chId && $this->transaction && ! empty($this->transaction->response)) {
            $stored = json_decode($this->transaction->response, true);
            if (is_array($stored)) {
                $piId = $this->extractPaymentIntentId($stored);
                $chId = $this->extractChargeId($stored);
            }
        }

        // If still missing and we have a Checkout Session ID (cs_...), fetch it from XPay
        if (! $piId && ! $chId && $ref !== '' && str_starts_with($ref, 'cs_')) {
            $savedAmount = $this->amount;
            $savedReason = $this->reason;
            $this->check($ref);
            $this->amount = $savedAmount;
            $this->reason = $savedReason;

            if ($this->hasError()) {
                return [null, null];
            }

            $piId = $this->payment_intent_id;
            $chId = $this->charge_id;
        }

        return [$piId, $chId];
    }

    protected function extractPaymentIntentId(array $data): ?string
    {
        if (! empty($data['paymentIntentId']) && is_string($data['paymentIntentId'])) {
            return $data['paymentIntentId'];
        }
        if (! empty($data['payment_intent_id']) && is_string($data['payment_intent_id'])) {
            return $data['payment_intent_id'];
        }
        if (! empty($data['paymentIntent'])) {
            if (is_string($data['paymentIntent'])) {
                return $data['paymentIntent'];
            }
            if (is_array($data['paymentIntent']) && ! empty($data['paymentIntent']['id'])) {
                return (string) $data['paymentIntent']['id'];
            }
        }
        return null;
    }

    protected function extractChargeId(array $data): ?string
    {
        if (! empty($data['chargeId']) && is_string($data['chargeId'])) {
            return $data['chargeId'];
        }
        if (! empty($data['paymentIntent']['charges'][0]['id'])) {
            return (string) $data['paymentIntent']['charges'][0]['id'];
        }
        if (! empty($data['charges'][0]['id'])) {
            return (string) $data['charges'][0]['id'];
        }
        return null;
    }

    // =========================================================================
    // Webhook Signature Verification
    // =========================================================================

    /**
     * Verify the `XPay-Signature: t=1730000000,v1=a1b2c3...` header using HMAC-SHA256.
     */
    public function verifyWebhookSignature(
        string $rawBody,
        ?string $signatureHeader,
        ?string $webhookSecret = null,
        int $tolerance = 300
    ): bool {
        $secret = $webhookSecret ?? $this->webhook_secret;
        if (empty($secret) || empty($signatureHeader)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$k, $v] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($k !== null && $v !== null) {
                $parts[$k] = $v;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (! $timestamp || ! $signature || ! ctype_digit((string) $timestamp)) {
            return false;
        }

        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

        return hash_equals($expected, $signature);
    }

    // =========================================================================
    // Status & Amount Helpers
    // =========================================================================

    public function getStatus(): ?string
    {
        $paymentStatus = $this->callbackData['paymentStatus'] ?? $this->callbackData['payment_status'] ?? null;
        if ($paymentStatus) {
            return strtoupper((string) $paymentStatus);
        }

        $status = $this->callbackData['status'] ?? null;
        return $status ? strtoupper((string) $status) : null;
    }

    public function paymentAccepted(): bool
    {
        $paymentStatus = strtolower((string) ($this->callbackData['paymentStatus'] ?? $this->callbackData['payment_status'] ?? ''));
        $status        = strtolower((string) ($this->callbackData['status'] ?? ''));

        if ($paymentStatus === 'paid') {
            return true;
        }

        return in_array($status, ['succeeded', 'paid', 'successful', 'captured'], true);
    }

    public function paymentCancelled(): bool
    {
        if ($this->paymentAccepted()) {
            return false;
        }

        $status   = strtolower((string) ($this->callbackData['status'] ?? ''));
        $piStatus = strtolower((string) ($this->callbackData['paymentIntent']['status'] ?? ''));

        return in_array($status, ['expired', 'canceled', 'cancelled', 'failed'], true)
            || in_array($piStatus, ['canceled', 'cancelled', 'payment_failed', 'failed'], true);
    }

    public function paymentPending(): bool
    {
        return ! $this->paymentAccepted() && ! $this->paymentCancelled();
    }

    public function refundAccepted(): bool
    {
        if (! isset($this->response) || ! $this->response->successful()) {
            return false;
        }
        $status = strtolower((string) ($this->callbackData['status'] ?? ''));
        return in_array($status, ['succeeded', 'pending', 'success'], true);
    }

    /**
     * Decimal amount reported by XPay for the checked session (converted from minor units).
     */
    public function getPaidAmount(): ?float
    {
        if (isset($this->callbackData['amountTotal']) && is_numeric($this->callbackData['amountTotal'])) {
            return $this->fromMinorUnits((int) $this->callbackData['amountTotal']);
        }
        if (isset($this->callbackData['amount']) && is_numeric($this->callbackData['amount'])) {
            return $this->fromMinorUnits((int) $this->callbackData['amount']);
        }
        return null;
    }

    /**
     * Uppercase currency code reported by XPay for the checked session.
     */
    public function getPaidCurrency(): ?string
    {
        $currency = $this->callbackData['currency'] ?? null;
        return $currency ? strtoupper((string) $currency) : null;
    }

    public function getTransactionUuid(): ?string { return $this->refrance; }
    public function getPaymentIntentId(): ?string { return $this->payment_intent_id; }
    public function getChargeId(): ?string        { return $this->charge_id; }
    public function getRefundId(): ?string        { return $this->refund_id; }
    public function getIframeUrl(): ?string       { return $this->iframe_url; }
    public function getTotalAmount(): ?float      { return $this->total_amount; }
    public function getData(): array              { return $this->callbackData; }
    public function hasTocken(): bool             { return false; }

    protected function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    protected function fromMinorUnits(int $minorAmount): float
    {
        return round($minorAmount / 100, 2);
    }

    // =========================================================================
    // Transaction Persistence
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
        $tx->refrance  = $this->refund_id ?: ($parent->refrance . '-refund-' . uniqid());
        $tx->amount    = $this->amount ?? 0;
        $tx->currency  = $parent->currency;
        $tx->response  = json_encode($this->callbackData ?: []);
        $tx->status    = 'success';
        $tx->parent_id = $parent->id;
        $tx->save();
    }

    // =========================================================================
    // HTTP Client
    // =========================================================================

    /**
     * Send a request with Bearer `sk_...` authentication and parse XPay's error format:
     *   { "error": { "type": "...", "code": "...", "message": "...", "param": "..." }, "request_id": "req_..." }
     */
    protected function request(string $method, string $url, array $data = []): void
    {
        $this->error  = false;
        $this->errors = [];

        $headers = [
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        if ($method === 'post') {
            $headers['Idempotency-Key'] = (string) Str::uuid();
        }

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
            Log::info('[Larapay][XPay] Response', [
                'status' => $this->response->status(),
                'body'   => $this->response->body(),
            ]);
        }

        if ($this->response->successful()) {
            return;
        }

        $json = $this->response->object();

        // Modern XPay error object: { "error": { "type": "...", "code": "...", "message": "..." } }
        if (isset($json->error)) {
            if (is_string($json->error)) {
                $this->errors[] = $json->error;
                $this->error    = $json->error;
                return;
            }
            if (is_object($json->error)) {
                $msg = trim((string) ($json->error->message ?? $json->error->code ?? ''));
                if (! empty($json->error->details) && is_array($json->error->details)) {
                    foreach ($json->error->details as $detail) {
                        $detailMsg = is_object($detail)
                            ? trim(($detail->field ?? '') . ': ' . ($detail->message ?? ''))
                            : (string) $detail;
                        if ($detailMsg !== '') {
                            $this->errors[] = $detailMsg;
                        }
                    }
                }
                if ($msg !== '') {
                    $this->errors[] = $msg;
                    $this->error    = $msg;
                    return;
                }
            }
        }

        $fallbackMessage = trim((string) ($json->message ?? ''));
        $this->error = $fallbackMessage !== ''
            ? $fallbackMessage
            : ($this->errors[0] ?? "{$this->response->status()}: {$this->response->reason()}");
    }

    public function json(): object|null
    {
        if (isset($this->response)) {
            return $this->response->object();
        }
        return $this->callbackData ? json_decode(json_encode($this->callbackData)) : null;
    }

    public function responseBody(): ?string
    {
        return isset($this->response) ? $this->response->body() : null;
    }
}

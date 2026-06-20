<?php

namespace Larapay\Core\Gateways;

use Illuminate\Support\Str;
use Illuminate\View\View;
use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;
use Larapay\Models\LarapayTransaction;
use Larapay\Core\Gateways\Payfort\Traits\Cart;
use Larapay\Core\Gateways\Payfort\Traits\Billing;

/**
 * Payfort / Amazon Payment Services (APS) gateway.
 *
 * Integration modes supported:
 *
 *  A) Hosted Checkout — pay() → getPayForm()
 *     Server builds a signed HTML form; browser POSTs it to APS.
 *     APS handles card entry on their hosted page, then POSTs result
 *     back to return_url (larapay.client-callback).
 *
 *  B) Check Status   — check()
 *     Server-to-server POST to verify a transaction's current state.
 *
 *  C) Refund         — refund()
 *     Server-to-server POST to refund a captured transaction.
 *
 * Config keys  (config/larapay.php → 'payfort'):
 *   live.access_code          – Live access code
 *   live.merchant_identifier  – Live merchant identifier
 *   live.sha_request_phrase   – Live SHA request phrase
 *   live.sha_response_phrase  – Live SHA response phrase
 *   sandbox.access_code          – Sandbox access code
 *   sandbox.merchant_identifier  – Sandbox merchant identifier
 *   sandbox.sha_request_phrase   – Sandbox SHA request phrase
 *   sandbox.sha_response_phrase  – Sandbox SHA response phrase
 *   sha_type        – "sha256" (default) | "sha512" | "sha1"
 *   language        – "en" (default) | "ar"
 *   command         – "PURCHASE" (default) | "AUTHORIZATION"
 *   return_url      – Override redirect-back URL (null = auto-route)
 *
 * @see https://paymentservices.amazon.com/docs/api/accepting-payments/hosted-checkout
 * @see https://paymentservices.amazon.com/docs/developer-resources/signature
 */
class Payfort extends LarapayBase implements LarapayInterface
{
    use Cart, Billing;

    // ── Endpoints ─────────────────────────────────────────────────────────────
    protected const CHECKOUT_SANDBOX = 'https://sbcheckout.payfort.com/FortAPI/paymentPage';
    protected const CHECKOUT_LIVE    = 'https://checkout.payfort.com/FortAPI/paymentPage';
    protected const API_SANDBOX      = 'https://sbpaymentservices.payfort.com/FortAPI/paymentApi';
    protected const API_LIVE         = 'https://paymentservices.payfort.com/FortAPI/paymentApi';

    // ── Success / on-hold status codes ────────────────────────────────────────
    protected const STATUS_PURCHASED     = '14';
    protected const STATUS_AUTHORIZED    = '02';  // 3DS pending or authorization held
    protected const STATUS_AUTH_SUCCESS  = '04';  // authorization approved
    protected const STATUS_CAPTURED      = '20';

    protected array $callbackData = [];

    public function __construct(
        protected string  $gateway,
        protected string  $mode,
        protected ?string $access_code         = null,
        protected ?string $merchant_identifier = null,
        protected ?string $sha_request_phrase  = null,
        protected ?string $sha_response_phrase = null,
        protected string  $sha_type            = 'sha256',
        protected string  $language            = 'en',
        protected string  $command             = 'PURCHASE',
        protected ?string $endpoint            = null,
        protected ?float  $amount              = null,
        protected ?string $currency            = null,
        protected ?string $return_url          = null,
        protected ?string $refrance            = null,
    ) {
        $this->access_code         = config("larapay.{$gateway}.{$mode}.access_code");
        $this->merchant_identifier = config("larapay.{$gateway}.{$mode}.merchant_identifier");
        $this->sha_request_phrase  = config("larapay.{$gateway}.{$mode}.sha_request_phrase");
        $this->sha_response_phrase = config("larapay.{$gateway}.{$mode}.sha_response_phrase");
        $this->sha_type            = config("larapay.{$gateway}.sha_type", 'sha256');
        $this->language            = config("larapay.{$gateway}.language", 'en');
        $this->command             = config("larapay.{$gateway}.command", 'PURCHASE');
        $this->currency            = Str::upper(config('larapay.currency', 'AED'));

        $this->return_url = config("larapay.{$gateway}.return_url")
            ?: route('larapay.client-callback', $gateway);

        $this->endpoint = $this->checkoutEndpoint();

        parent::__construct();
    }

    // =========================================================================
    // Interface
    // =========================================================================

    public function init(): static { return $this; }

    public function set(
        ?string $uid              = null,
        ?string $currency         = null,
        ?float  $amount           = null,
        mixed   $cart_id          = null,
        ?string $cart_description = null,
        ?string $return_url       = null,
        ?string $refrance         = null,
        ?string $command          = null,
        ?string $language         = null,
    ): static {
        $this->uid              = $uid              ?? $this->uid;
        $this->currency         = $currency         ? Str::upper($currency) : $this->currency;
        $this->amount           = $amount           ?? $this->amount;
        $this->cart_id          = $cart_id          ?? $this->cart_id;
        $this->cart_description = $cart_description ?? $this->cart_description;
        $this->return_url       = $return_url       ?? $this->return_url;
        $this->refrance         = $refrance         ?? $this->refrance;
        $this->command          = $command          ?? $this->command;
        $this->language         = $language         ?? $this->language;
        return $this;
    }

    // =========================================================================
    // Mode A — Hosted Checkout (browser-side POST form)
    // =========================================================================

    /**
     * Build the signed parameters for the APS hosted checkout form.
     * Returns $this — call getPayForm() to render the Blade view.
     */
    public function pay(?float $amount = null): static
    {
        if ($this->hasError()) return $this;

        $this->amount = $amount ?? $this->amount;

        if (! $this->access_code || ! $this->merchant_identifier || ! $this->sha_request_phrase) {
            $this->error = __('Payfort access_code, merchant_identifier and sha_request_phrase are required.');
            return $this;
        }
        if (! $this->amount || ! $this->currency) {
            $this->error = __('Amount and currency are required.');
            return $this;
        }
        if (! $this->customer_email) {
            $this->error = __('customer_email is required for Payfort payments.');
            return $this;
        }

        return $this;
    }

    /**
     * Return the Blade view containing the auto-submitting hidden form.
     * Must call pay() first (or set amount/cart/billing directly).
     */
    public function getPayForm(?string $submitLabel = null): View
    {
        $params = $this->buildCheckoutParams();
        $params['signature'] = $this->generateSignature($params, $this->sha_request_phrase);

        return view('larapay::gateways.payfort.form', [
            'action'      => $this->checkoutEndpoint(),
            'params'      => $params,
            'submitLabel' => $submitLabel ?? 'Pay Securely',
            'mode'        => $this->mode,
            'amount'      => number_format($this->amount, 2),
            'currency'    => $this->currency,
            'storeName'   => config('app.name', 'Store'),
        ]);
    }

    // =========================================================================
    // Mode B — Check Status (server-to-server)
    // =========================================================================

    /**
     * Query the current status of a transaction.
     * Set refrance (merchant_reference) or fort_id before calling.
     */
    public function check(): static
    {
        if ($this->hasError()) return $this;

        if (! $this->refrance) {
            $this->error = __('merchant_reference (refrance) is required to check Payfort status.');
            return $this;
        }

        $params = [
            'query_command'       => 'CHECK_STATUS',
            'access_code'         => $this->access_code,
            'merchant_identifier' => $this->merchant_identifier,
            'merchant_reference'  => $this->refrance,
            'language'            => $this->language,
        ];
        $params['signature'] = $this->generateSignature($params, $this->sha_request_phrase);

        $this->post($this->apiEndpoint(), $params, ['Content-Type' => 'application/json']);

        if (! $this->hasError()) {
            $data = (array) ($this->json() ?? []);
            $this->callbackData = $data;
            $this->refrance     = $data['fort_id'] ?? $data['merchant_reference'] ?? $this->refrance;
        }

        return $this;
    }

    // =========================================================================
    // Mode C — Refund (server-to-server)
    // =========================================================================

    /**
     * Refund a captured transaction.
     *
     * @param  float  $amount   Amount to refund (in normal decimal, e.g. 50.00)
     */
    public function refund(?float $amount = null): static
    {
        if ($this->hasError()) return $this;

        $this->amount = $amount ?? $this->amount;

        if (! $this->refrance) {
            $this->error = __('fort_id / merchant_reference (refrance) is required for Payfort refund.');
            return $this;
        }
        if (! $this->amount) {
            $this->error = __('Amount is required for Payfort refund.');
            return $this;
        }

        $params = [
            'command'             => 'REFUND',
            'access_code'         => $this->access_code,
            'merchant_identifier' => $this->merchant_identifier,
            'merchant_reference'  => $this->uid,
            'amount'              => $this->formatAmount($this->amount, $this->currency),
            'currency'            => $this->currency,
            'language'            => $this->language,
            'fort_id'             => $this->refrance,
        ];
        $params['signature'] = $this->generateSignature($params, $this->sha_request_phrase);

        $this->post($this->apiEndpoint(), $params, ['Content-Type' => 'application/json']);

        if (! $this->hasError()) {
            $this->callbackData = (array) ($this->json() ?? []);
        }

        return $this;
    }

    // =========================================================================
    // Callback verification (return_url POST from APS)
    // =========================================================================

    /**
     * Verify the signature on the APS callback and store the result.
     * Call this in your clientCallback / serverCallback handler.
     *
     * @param  array|null  $postData  defaults to request()->post()
     */
    public function verifyCallback(?array $postData = null): static
    {
        $data = $postData ?? request()->post();

        if (empty($data['signature'])) {
            $this->error = __('Missing Payfort callback signature.');
            return $this;
        }

        $received = $data['signature'];
        unset($data['signature']);

        $expected = $this->generateSignature($data, $this->sha_response_phrase);

        if (! hash_equals($expected, strtolower($received))) {
            $this->error = __('Payfort callback signature verification failed.');
            return $this;
        }

        $this->callbackData = array_merge($data, ['signature' => $received]);
        $this->refrance     = $data['fort_id'] ?? $data['merchant_reference'] ?? $this->refrance;

        return $this;
    }

    // =========================================================================
    // Status helpers
    // =========================================================================

    public function paymentAccepted(): bool
    {
        $status = (string) ($this->callbackData['status'] ?? '');
        return in_array($status, [self::STATUS_PURCHASED, self::STATUS_AUTH_SUCCESS, self::STATUS_CAPTURED]);
    }

    public function paymentCancelled(): bool
    {
        $status = (string) ($this->callbackData['status'] ?? '');
        return ! empty($status)
            && ! in_array($status, [
                self::STATUS_PURCHASED,
                self::STATUS_AUTHORIZED,
                self::STATUS_AUTH_SUCCESS,
                self::STATUS_CAPTURED,
            ]);
    }

    public function hasTocken(): bool { return false; }

    // =========================================================================
    // Transaction persistence
    // =========================================================================

    public function register(): void
    {
        $tx           = new LarapayTransaction;
        $tx->type     = strtolower($this->command);
        $tx->uid      = $this->uid;
        $tx->gateway  = $this->gateway;
        $tx->refrance = $this->refrance ?? ($this->cart_id ?? null);
        $tx->amount   = $this->amount   ?? 0;
        $tx->currency = $this->currency ?? null;
        $tx->response = json_encode($this->callbackData ?? []);
        $tx->status   = 'pending';
        $tx->save();
    }

    public function registerRefund($parentTransaction): void
    {
        $tx            = new LarapayTransaction;
        $tx->type      = 'refund';
        $tx->uid       = uniqid();
        $tx->gateway   = $this->gateway;
        $tx->refrance  = $this->callbackData['fort_id'] ?? $this->refrance ?? null;
        $tx->amount    = $this->amount   ?? 0;
        $tx->currency  = $this->currency ?? null;
        $tx->response  = json_encode($this->callbackData ?? []);
        $tx->status    = 'success';
        $tx->parent_id = $parentTransaction->id;

        if (! LarapayTransaction::where('refrance', $tx->refrance)
                                 ->where('gateway',  $tx->gateway)->first()) {
            $tx->save();
        }
    }

    public function json(): object|null
    {
        if (isset($this->response)) {
            return parent::json();
        }
        return $this->callbackData ? (object) $this->callbackData : null;
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    /**
     * Build the hosted checkout parameter array (without signature).
     */
    protected function buildCheckoutParams(): array
    {
        $params = [
            'command'             => $this->command,
            'access_code'         => $this->access_code,
            'merchant_identifier' => $this->merchant_identifier,
            'merchant_reference'  => $this->cart_id ?? $this->uid,
            'amount'              => (string) $this->formatAmount($this->amount, $this->currency),
            'currency'            => $this->currency,
            'language'            => $this->language,
            'customer_email'      => $this->customer_email,
            'return_url'          => $this->return_url,
        ];

        if ($this->customer_name)  $params['customer_name']      = $this->customer_name;
        if ($this->customer_ip)    $params['customer_ip']        = $this->customer_ip;
        if ($this->phone_number)   $params['phone_number']       = $this->phone_number;
        if ($this->cart_description) $params['order_description'] = Str::limit($this->cart_description, 150);

        return $params;
    }

    /**
     * Generate APS HMAC signature.
     *
     * Algorithm:
     *   1. Sort params alphabetically by key
     *   2. Concat as  key=value  (no separator between pairs)
     *   3. Wrap:  {phrase}{concat}{phrase}
     *   4. hash( sha_type, wrapped_string )
     */
    protected function generateSignature(array $params, ?string $phrase): string
    {
        // Exclude signature itself if present
        unset($params['signature']);

        ksort($params);

        $str = $phrase;
        foreach ($params as $key => $value) {
            $str .= "{$key}={$value}";
        }
        $str .= $phrase;

        return hash($this->sha_type, $str);
    }

    /**
     * Convert decimal amount to APS integer format (smallest currency unit).
     * EGP/AED/SAR/USD → × 100   |   KWD/BHD/OMR → × 1000
     */
    protected function formatAmount(float $amount, string $currency): int
    {
        $three = ['KWD', 'BHD', 'OMR', 'JOD', 'IQD', 'LYD', 'TND'];
        $multiplier = in_array(strtoupper($currency), $three) ? 1000 : 100;
        return (int) round($amount * $multiplier);
    }

    protected function checkoutEndpoint(): string
    {
        return $this->mode === 'live' ? self::CHECKOUT_LIVE : self::CHECKOUT_SANDBOX;
    }

    protected function apiEndpoint(): string
    {
        return $this->mode === 'live' ? self::API_LIVE : self::API_SANDBOX;
    }
}

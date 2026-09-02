<?php

namespace Larapay\Core\Gateways;

use Illuminate\Support\Str;
use Larapay\Core\LarapayBase;
use Larapay\Core\LarapayInterface;
use Larapay\Models\LarapayTransaction;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPal extends LarapayBase implements LarapayInterface
{   
  
  public function __construct(
    protected ?string $gateway = 'paypal',
    protected ?string $provider = null,
    protected ?string $mode = 'sandbox',
    protected ?float $amount = null, 
    protected ?string $profile_id = null, 
    protected ?string $tran_type = null, 
    protected ?string $tran_class = null, 
    protected ?string $callback = null, 
    protected ?string $return = null, 
    protected ?string $cart_id = null, 
    protected ?string $cart_currency = null, 
    protected ?string $cart_amount = null, 
    protected ?string $cart_description = null, 
  )
  {
    $this->gateway = $gateway;
    $this->mode = config("larapay.mode");
    $this->profile_id = config("larapay.{$this->gateway}.profile_id");
    $this->tran_type = 'sale';
    $this->tran_class = 'ecom';
    $this->callback = config("larapay.{$this->gateway}.callback");
    $this->return = config("larapay.{$this->gateway}.return");
  }

  public function init(): PayPal
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

  protected function getPaypalConfig(): array
  {
      $mode = $this->mode === 'live' ? 'live' : 'sandbox';
      $clientId = config("larapay.paypal.{$mode}.client_id");
      
      if (empty($clientId)) {
          throw new \Larapay\Core\Exceptions\GatewayConfigurationException("PayPal {$mode} client_id is missing in configuration.");
      }

      return [
          'mode'    => $mode,
          $mode => [
              'client_id'         => $clientId,
              'client_secret'     => config("larapay.paypal.{$mode}.client_secret"),
              'app_id'            => config("larapay.paypal.{$mode}.app_id"),
          ],
          'payment_action' => config('larapay.paypal.payment_action', 'Sale'),
          'currency'       => $this->currency,
          'notify_url'     => $this->server_callback,
          'locale'         => config('larapay.paypal.locale', 'en_US'),
          'validate_ssl'   => config('larapay.paypal.validate_ssl', true),
      ];
  }

  public function pay(?float $amount = null): static
  {
      if ($this->hasError()) return $this;
      $this->amount = $amount ?? $this->amount;

      if (! $this->amount || ! $this->currency) {
          $this->error = 'Amount and currency are required for PayPal payment.';
          return $this;
      }

      try {
          $provider = new PayPalClient;
          $provider->setApiCredentials($this->getPaypalConfig());
          $tokenRes = $provider->getAccessToken();
          if (empty($tokenRes['access_token'])) {
              $this->error = 'PayPal authentication failed. Check credentials.';
              return $this;
          }

          $orderData = [
              'intent' => 'CAPTURE',
              'purchase_units' => [
                  [
                      'reference_id' => $this->cart_id ?? $this->uid,
                      'description'  => $this->cart_description,
                      'amount' => [
                          'currency_code' => $this->currency,
                          'value' => number_format((float) $this->amount, 2, '.', '')
                      ]
                  ]
              ],
              'application_context' => [
                  'cancel_url' => $this->client_callback . '?status=cancel',
                  'return_url' => $this->client_callback . '?status=success',
              ]
          ];

          $order = $provider->createOrder($orderData);

          if (isset($order['id']) && $order['status'] !== 'FAILED') {
              foreach ($order['links'] as $link) {
                  if ($link['rel'] === 'approve') {
                      $this->redirect = $link['href'];
                      return $this;
                  }
              }
              $this->error = 'No approve link returned from PayPal.';
          } else {
              $this->error = $order['message'] ?? 'PayPal order creation failed.';
          }
      } catch (\Throwable $e) {
          $this->error = $e->getMessage();
      }

      return $this;
  }

  public function check2(array $data): static
  {
      $token = $data['token'] ?? null;
      $status = $data['status'] ?? null;

      if ($status === 'cancel') {
          $this->error = 'Payment was cancelled by the user.';
          return $this;
      }

      if (!$token) {
          $this->error = 'No PayPal token provided in response.';
          return $this;
      }

      try {
          $provider = new PayPalClient;
          $provider->setApiCredentials($this->getPaypalConfig());
          $provider->getAccessToken();

          $result = $provider->capturePaymentOrder($token);

          if (isset($result['status']) && $result['status'] === 'COMPLETED') {
              // Successfully captured
              return $this;
          } else {
              $this->error = $result['message'] ?? 'PayPal payment capture failed.';
          }
      } catch (\Throwable $e) {
          $this->error = $e->getMessage();
      }

      return $this;
  }
}

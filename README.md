# Larapay

[![Total Downloads](https://img.shields.io/packagist/dt/ahmedsaoud31/larapay)](https://packagist.org/packages/ahmedsaoud31/larapay)
[![License](https://img.shields.io/badge/license-MIT-green)](https://en.wikipedia.org/wiki/MIT_License)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-blue)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-%3E%3D10.0-red)](https://laravel.com)

A Laravel payment gateway package that provides a unified, fluent API for multiple payment providers in the Middle East and globally. It features a robust Enterprise-Ready architecture using the **Manager Pattern**, **Events**, **Exceptions**, and **Debug Logging**.

---

## Supported Gateways

| Gateway | Region | Mode |
|---|---|---|
| [PayTabs](https://www.paytabs.com) | MENA | Hosted form / Direct API |
| [PayMob](https://paymob.com) | Egypt / MENA | Hosted checkout (unified) |
| [Kashier](https://kashier.io) | Egypt | HPP redirect / Session API |
| [Payfort (Amazon Payment Services)](https://paymentservices.amazon.com) | MENA | Hosted checkout |
| [PayPal](https://paypal.com) | Global | Hosted checkout / API |
| [Tab Travel](https://www.tab.travel) | Global | Hosted checkout / API |

---

## Requirements

- PHP >= 8.0
- Laravel >= 10.0
- Guzzle >= 7.9

---

## Installation

```bash
composer require ahmedsaoud31/larapay
```

Publish all package files (config, assets, migrations, views, lang):

```bash
php artisan vendor:publish --tag=larapay
```

*(You can also publish specific parts using `--tag=larapay-config`, `--tag=larapay-migrations`, etc.)*

Run migrations (creates the `larapay_transactions` table):

```bash
php artisan migrate
```

---

## Configuration

All settings live in `config/larapay.php`. You can configure routes, middlewares, debug mode, and active gateways.

```env
LARAPAY_MODE=sandbox        # sandbox or live
LARAPAY_GATEWAY=paytabs     # default gateway
LARAPAY_CURRENCY=EGP        # default currency
LARAPAY_DEBUG=true          # log API requests/responses
```

**Customizing Routes:**
Inside `config/larapay.php`, you can customize the callback routes prefix and middleware:
```php
'routes' => [
    'prefix' => 'larapay',
    'middleware' => ['web'],
],
```

---

## Events & Exceptions

**Events:** 
The package fires native Laravel events that you can listen to in your `EventServiceProvider`:
- `Larapay\Events\PaymentSucceeded`
- `Larapay\Events\PaymentFailed`
- `Larapay\Events\PaymentRefunded`

**Exceptions:** 
- `Larapay\Core\Exceptions\GatewayConfigurationException`: Thrown when a gateway's required configuration keys are missing.
- `Larapay\Core\Exceptions\PaymentGatewayException`: Thrown when a gateway returns an unrecoverable error during initialization.

---

## Gateways

### PayTabs

```env
PAYTABS_PROFILE_ID=
PAYTABS_SANDBOX_SERVER_KEY=
PAYTABS_SANDBOX_CLIENT_KEY=
PAYTABS_END_POINT=https://secure-egypt.paytabs.com/
```

**Usage:**

```php
use Larapay\Facades\Larapay;

$pay = Larapay::driver('paytabs') // or Larapay::init('paytabs')

    ->billing(
        name: 'Ahmed Ali',
        email: 'ahmed@example.com',
        phone: '01012345678',
        address: 'Cairo, Egypt',
        city: 'Cairo',
    )
    ->cart(id: $order->id, description: 'Order #'.$order->id, amount: 500.00)
    ->pay();

if (!$pay->hasError()) {
    $pay->register();
    return redirect($pay->getRedirect());
}
echo $pay->getError();
```

**Check transaction:**

```php
$check = Larapay::driver('paytabs')
    ->set(refrance: $transaction->refrance)
    ->check();

if ($check->paymentAccepted()) {
    // mark as paid
}
```

**Refund:**

```php
Larapay::driver('paytabs')
    ->set(refrance: $transaction->refrance)
    ->cart(id: $response->cart_id, description: $response->cart_description, amount: $response->cart_amount)
    ->refund(50.00);
```

---

### PayMob

```env
PAYMOB_SANDBOX_API_KEY=
PAYMOB_SANDBOX_SECRET_KEY=
PAYMOB_SANDBOX_PUBLIC_KEY=
```

**Usage:**

```php
$pay = Larapay::driver('paymob')
    ->billing(
        first_name: 'Ahmed',
        last_name: 'Ali',
        email: 'ahmed@example.com',
        phone: '+201012345678',
    )
    ->set(amount: 300)
    ->checkout();

if (!$pay->hasError()) {
    $pay->register();
    return redirect($pay->getRedirect());
}
```

---

### Kashier

Kashier supports two integration flows:

**A) HPP Redirect** — build a signed URL and redirect the user.

```env
KASHIER_MID=MID-xxxx-xxx
KASHIER_SANDBOX_API_KEY=
KASHIER_SANDBOX_SECRET_KEY=
```

```php
$pay = Larapay::driver('kashier')
    ->billing(name: 'Ahmed Ali', email: 'ahmed@example.com')
    ->cart(id: $order->id, description: 'Order #'.$order->id, amount: 150.00)
    ->pay();

if (!$pay->hasError()) {
    $pay->register();
    return redirect($pay->getRedirect());
}
```

**B) Session API** — create a session server-side then show an order-review page that links to Kashier's hosted session URL.

```php
$gateway = Larapay::driver('kashier')
    ->billing(email: 'ahmed@example.com')
    ->cart(id: $order->id, description: 'Order #'.$order->id, amount: 150.00)
    ->createSession();

if ($gateway->hasError()) {
    abort(500, $gateway->getError());
}

$gateway->register();
return $gateway->getPayForm(storeName: 'My Shop');
```

**Callback verification** (in `clientCallback`):

```php
$check = Larapay::driver('kashier')
    ->check(request()->query());

if ($check->paymentAccepted()) {
    $transaction->status = 'success';
}
```

**Optional:** Restrict payment methods:

```php
Larapay::driver('kashier')->enableCard()->enableWallet()-> ...
```

---

### Payfort (Amazon Payment Services)

```env
PAYFORT_SANDBOX_ACCESS_CODE=
PAYFORT_SANDBOX_MERCHANT_ID=
PAYFORT_SANDBOX_SHA_REQUEST_PHRASE=
PAYFORT_SANDBOX_SHA_RESPONSE_PHRASE=
PAYFORT_CURRENCY=AED          # must match your APS account currency
```

**Usage:**

```php
$pay = Larapay::driver('payfort')
    ->billing(
        email: 'ahmed@example.com',
        name:  'Ahmed Ali',
        ip:    request()->ip(),
    )
    ->cart(id: $order->id, description: 'Order #'.$order->id, amount: 250.00)
    ->pay();

if (!$pay->hasError()) {
    $pay->register();
    return $pay->getPayForm();   // renders auto-submitting redirect page
}
```

The `getPayForm()` view auto-submits a hidden form to the APS hosted checkout page. After payment, APS POSTs the result back to `larapay.client-callback`.

**Check status:**

```php
$check = Larapay::driver('payfort')
    ->set(refrance: $transaction->refrance)
    ->check();
```

**Refund:**

```php
Larapay::driver('payfort')
    ->set(refrance: $transaction->fort_id, currency: 'AED')
    ->refund(50.00);
```

**Signature algorithm:**
Params sorted alphabetically → concatenated as `key=value` → wrapped with SHA phrase → SHA-256 hash. Fully implemented — no manual calculation needed.

---

### PayPal

Uses the `srmklive/paypal` package under the hood, wrapped in Larapay's fluent API.

```env
PAYPAL_MODE=sandbox
PAYPAL_SANDBOX_CLIENT_ID=
PAYPAL_SANDBOX_CLIENT_SECRET=
```

**Usage:**

```php
$pay = Larapay::driver('paypal')
    ->cart(id: $order->id, description: 'Order #'.$order->id, amount: 100.00)
    ->pay();

if (!$pay->hasError()) {
    $pay->register();
    return redirect()->away($pay->getRedirect());
}
```

**Callback verification:**

```php
$check = Larapay::driver('paypal')->check2(request()->query());

if ($check->paymentAccepted()) {
    $transaction->status = 'success';
}
```

---

### Tab Travel

```env
TAB_API_KEY=
TAB_CURRENCY=USD
TAB_MERCHANT_CODE=
```

**Usage:**

```php
$pay = Larapay::driver('tab')
    ->billing(name: 'Ahmed Ali', email: 'ahmed@example.com')
    ->cart(id: $order->id, description: 'Order #'.$order->id, amount: 200.00)
    ->pay();

if (!$pay->hasError()) {
    $pay->register();
    return redirect()->away($pay->getRedirect());
}
```

**Callback verification:**

```php
$check = Larapay::driver('tab')->check2(request()->query());

if ($check->paymentAccepted()) {
    $transaction->status = 'success';
}
```

---

## Callbacks

All gateways share the same callback routes. They are automatically registered by the service provider:

GET|POST  /{config:prefix}/{gateway}/client-callback   → clientCallback($gateway)
GET|POST  /{config:prefix}/{gateway}/server-callback   → serverCallback($gateway)

The `clientCallback` controller method handles each gateway's specific return format, updates the `larapay_transactions` table automatically, and fires `PaymentSucceeded` or `PaymentFailed` events.

---

## Transaction Model

All payments are recorded in `larapay_transactions`:

| Column | Description |
|---|---|
| `uid` | Your internal unique reference (matches what you sent to the gateway) |
| `gateway` | Gateway name e.g. `paytabs` |
| `refrance` | Gateway's transaction ID / fort_id / tran_ref |
| `amount` | Decimal amount |
| `currency` | ISO currency code |
| `status` | `pending` → `success` / `cancelled` |
| `response` | Full JSON gateway response |
| `parent_id` | Points to original transaction for refunds |

**Attach to any model (polymorphic):**

```php
$transaction->transactionable()->associate($order)->save();
```

---

## Test Routes

The package registers these routes for quick testing:

| Route | Description |
|---|---|
| `GET /larapay/test` | PayTabs hosted form test |
| `GET /larapay/paymob` | PayMob checkout test |
| `GET /larapay/kashier` | Kashier HPP redirect test |
| `GET /larapay/kashier/form` | Kashier Session API + order review page |
| `GET /larapay/payfort` | Payfort hosted checkout test |

---

## Fluent API Reference

All gateway instances share these chainable methods:

```php
use Larapay\Facades\Larapay;

Larapay::driver('paypal') // get driver (new Manager pattern)
Larapay::init('paypal') // backward compatible alias for driver()
->billing(...)          // set customer details

->cart(...)             // set order id, description, amount, currency
->set(...)              // set any property (uid, refrance, currency, etc.)
->pay()                 // initiate payment (builds redirect URL or form params)
->checkout()            // PayMob unified checkout
->createSession()       // Kashier: create a payment session via API
->getPayForm()          // return Blade view (hosted form or order review)
->check()               // verify transaction status / callback signature
->checkSession()        // Kashier: poll session status via API
->refund($amount)       // initiate refund
->verifyCallback()      // Payfort: verify APS callback signature
->register()            // save pending LarapayTransaction to DB
->registerRefund($tx)   // save refund LarapayTransaction to DB
->hasError()            // bool — check if last operation failed
->getError()            // string — last error message
->hasRedirect()         // bool — check if a redirect URL was set
->getRedirect()         // string — redirect URL
->paymentAccepted()     // bool — payment was successful
->paymentCancelled()    // bool — payment failed / cancelled
->json()                // object — raw gateway response
```

---

## Custom Gateways

Because Larapay uses the Laravel Manager pattern, you can easily extend it to add your own custom gateway drivers without modifying the package source:

```php
use Larapay\Facades\Larapay;

public function boot()
{
    Larapay::extend('stripe', function ($app) {
        return new StripeGateway(); // Must implement LarapayInterface
    });
}
```

---

## License

MIT — [Ahmed Aboelsaoud](mailto:ahmedsaoud31@gmail.com)

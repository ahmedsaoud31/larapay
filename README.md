# Larapay

[![Total Downloads](https://img.shields.io/packagist/dt/ahmedsaoud31/larapay)](https://packagist.org/packages/ahmedsaoud31/larapay)
[![License](https://img.shields.io/badge/license-MIT-green)](https://en.wikipedia.org/wiki/MIT_License)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-blue)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-%3E%3D10.0-red)](https://laravel.com)

A Laravel payment gateway package that provides a unified, fluent API for multiple payment providers in the Middle East and globally.

---

## Supported Gateways

| Gateway | Region | Mode |
|---|---|---|
| [PayTabs](https://www.paytabs.com) | MENA | Hosted form / Direct API |
| [PayMob](https://paymob.com) | Egypt / MENA | Hosted checkout (unified) |
| [Kashier](https://kashier.io) | Egypt | HPP redirect / Session API |
| [Payfort (Amazon Payment Services)](https://paymentservices.amazon.com) | MENA | Hosted checkout |
| [PayPal](https://paypal.com) | Global | *(stub — not yet implemented)* |

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

Publish the config file:

```bash
php artisan vendor:publish --tag=larapay-config
```

Run migrations (creates the `larapay_transactions` table):

```bash
php artisan migrate
```

---

## Configuration

All settings live in `config/larapay.php`. Set your active gateway and mode in `.env`:

```env
LARAPAY_MODE=sandbox        # sandbox or live
LARAPAY_GATEWAY=paytabs     # default gateway
LARAPAY_CURRENCY=EGP        # default currency
```

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
$pay = Larapay::init(gateway: 'paytabs')
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
$check = Larapay::init(gateway: 'paytabs')
    ->set(refrance: $transaction->refrance)
    ->check();

if ($check->paymentAccepted()) {
    // mark as paid
}
```

**Refund:**

```php
Larapay::init(gateway: 'paytabs')
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
$pay = Larapay::init(gateway: 'paymob')
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
$pay = Larapay::init(gateway: 'kashier')
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
$gateway = Larapay::init(gateway: 'kashier')
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
$check = Larapay::init(gateway: 'kashier')
    ->check(request()->query());

if ($check->paymentAccepted()) {
    $transaction->status = 'success';
}
```

**Optional:** Restrict payment methods:

```php
Larapay::init(gateway: 'kashier')->enableCard()->enableWallet()-> ...
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
$pay = Larapay::init(gateway: 'payfort')
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
$check = Larapay::init(gateway: 'payfort')
    ->set(refrance: $transaction->refrance)
    ->check();
```

**Refund:**

```php
Larapay::init(gateway: 'payfort')
    ->set(refrance: $transaction->fort_id, currency: 'AED')
    ->refund(50.00);
```

**Signature algorithm:**
Params sorted alphabetically → concatenated as `key=value` → wrapped with SHA phrase → SHA-256 hash. Fully implemented — no manual calculation needed.

---

## Callbacks

All gateways share the same callback routes. They are automatically registered by the service provider:

```
GET|POST  /larapay/{gateway}/client-callback   → clientCallback($gateway)
GET|POST  /larapay/{gateway}/server-callback   → serverCallback($gateway)
```

The `clientCallback` controller method handles each gateway's specific return format and updates the `larapay_transactions` table automatically.

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

## License

MIT — [Ahmed Aboelsaoud](mailto:ahmedsaoud31@gmail.com)

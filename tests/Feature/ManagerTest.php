<?php

use Larapay\Facades\Larapay;
use Larapay\Core\Gateways\PayPal;
use Larapay\Core\Gateways\Kashier;

it('resolves default gateway from manager', function () {
    $gateway = Larapay::driver();
    expect($gateway)->toBeInstanceOf(PayPal::class);
});

it('resolves specific gateway', function () {
    $gateway = Larapay::driver('kashier');
    expect($gateway)->toBeInstanceOf(Kashier::class);
});

it('supports backward compatible init method', function () {
    $gateway = Larapay::init('paypal');
    expect($gateway)->toBeInstanceOf(PayPal::class);
});

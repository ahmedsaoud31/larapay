<?php

use Illuminate\Support\Facades\Route;
use Larapay\Larapay;

Route::get('/larapay/form', function () {
  $larapay = new Larapay;
  $clientKey = $larapay
                ->init(gateway: 'paytabs')
                ->getClientKey();
  return view('larapay::gateways.paytabs.form', ['clientKey' => $clientKey]);
})->name('larapay.pay');
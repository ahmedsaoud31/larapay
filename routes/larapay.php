<?php

use Larapay\Larapay;
use Illuminate\Support\Facades\Route;
use Larapay\Controllers\LarapayController;


//Route::resource('larapay', LarapayController::class);
Route::get('/larapay', [LarapayController::class, 'landing'])->name('larapay.landing');
Route::get('/larapay/lang/{locale}', [LarapayController::class, 'setLocale'])->name('larapay.lang');
Route::post('/larapay/refund-action', [LarapayController::class, 'refundAction'])->name('larapay.refund-action');
Route::post('/larapay/check-action', [LarapayController::class, 'checkAction'])->name('larapay.check-action');
Route::post('/larapay/test-pay', [LarapayController::class, 'testPay'])->name('larapay.test-pay');
Route::get('/larapay/paymob', [LarapayController::class, 'paymob'])->name('larapay.paymob');
Route::get('/larapay/kashier', [LarapayController::class, 'kashier'])->name('larapay.kashier');
Route::get('/larapay/kashier/form', [LarapayController::class, 'kashierForm'])->name('larapay.kashier-form');
Route::get('/larapay/payfort', [LarapayController::class, 'payfort'])->name('larapay.payfort');
Route::get('/larapay/run', [LarapayController::class, 'run'])->name('larapay.run');
Route::get('/larapay/refund', [LarapayController::class, 'refund'])->name('larapay.refund');
Route::get('/larapay/check', [LarapayController::class, 'check'])->name('larapay.check');
Route::get('/larapay/test', [LarapayController::class, 'test'])->name('larapay.test');
Route::any('/larapay/form', [LarapayController::class, 'form'])->name('larapay.form');
Route::any('/larapay/{gateway}/server-callback', [LarapayController::class, 'serverCallback'])->name('larapay.server-callback');
Route::any('/larapay/{gateway}/client-callback', [LarapayController::class, 'clientCallback'])->name('larapay.client-callback');
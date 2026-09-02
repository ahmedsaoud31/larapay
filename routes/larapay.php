<?php

use Larapay\Larapay;
use Illuminate\Support\Facades\Route;
use Larapay\Controllers\LarapayController;


//Route::resource('', LarapayController::class);
Route::get('/', [LarapayController::class, 'landing'])->name('larapay.landing');
Route::get('/lang/{locale}', [LarapayController::class, 'setLocale'])->name('larapay.lang');
Route::post('/refund-action', [LarapayController::class, 'refundAction'])->name('larapay.refund-action');
Route::post('/check-action', [LarapayController::class, 'checkAction'])->name('larapay.check-action');
Route::post('/test-pay', [LarapayController::class, 'testPay'])->name('larapay.test-pay');
Route::get('/paymob', [LarapayController::class, 'paymob'])->name('larapay.paymob');
Route::get('/kashier', [LarapayController::class, 'kashier'])->name('larapay.kashier');
Route::get('/kashier/form', [LarapayController::class, 'kashierForm'])->name('larapay.kashier-form');
Route::get('/payfort', [LarapayController::class, 'payfort'])->name('larapay.payfort');
Route::get('/run', [LarapayController::class, 'run'])->name('larapay.run');
Route::get('/refund', [LarapayController::class, 'refund'])->name('larapay.refund');
Route::get('/check', [LarapayController::class, 'check'])->name('larapay.check');
Route::get('/test', [LarapayController::class, 'test'])->name('larapay.test');
Route::any('/form', [LarapayController::class, 'form'])->name('larapay.form');
Route::any('/{gateway}/server-callback', [LarapayController::class, 'serverCallback'])->name('larapay.server-callback');
Route::any('/{gateway}/client-callback', [LarapayController::class, 'clientCallback'])->name('larapay.client-callback');
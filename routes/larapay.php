<?php

use Larapay\Larapay;
use Illuminate\Support\Facades\Route;
use Larapay\Controllers\LarapayController;


//Route::resource('larapay', LarapayController::class);
Route::get('/larapay/paymob', [LarapayController::class, 'paymob'])->name('larapay.paymob');
Route::get('/larapay/run', [LarapayController::class, 'run'])->name('larapay.run');
Route::get('/larapay/refund', [LarapayController::class, 'refund'])->name('larapay.refund');
Route::get('/larapay/check', [LarapayController::class, 'check'])->name('larapay.check');
Route::get('/larapay/test', [LarapayController::class, 'test'])->name('larapay.test');
Route::any('/larapay/form', [LarapayController::class, 'form'])->name('larapay.form');
Route::any('/larapay/{gateway}/server-callback', [LarapayController::class, 'serverCallback'])->name('larapay.server-callback');
Route::any('/larapay/{gateway}/client-callback"', [LarapayController::class, 'clientCallback'])->name('larapay.client-callback');
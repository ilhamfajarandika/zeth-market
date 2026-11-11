<?php

use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\Webhook\MeloCallbackController;
use App\Http\Controllers\Webhook\TripayCallbackController;
use Illuminate\Support\Facades\Route;

Route::controller(StorefrontController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/game/{game}', 'game')->name('game.show');
    Route::get('/invoice/{order}', 'invoice')->name('invoice.show');
});

Route::prefix('webhook')->name('webhook.')->middleware('throttle:120,1')->group(function () {
    Route::post('/tripay', TripayCallbackController::class)->name('tripay');
    Route::post('/melo', MeloCallbackController::class)->name('melo');
});

<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\Webhook\DuitkuCallbackController;
use App\Http\Controllers\Webhook\MeloCallbackController;
use Illuminate\Support\Facades\Route;

Route::controller(StorefrontController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/game/{game}', 'game')->name('game.show');
    Route::get('/invoice/{order}', 'invoice')->name('invoice.show');
});

Route::middleware('guest')->controller(CustomerAuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', 'showRegister')->name('register');
    Route::post('/register', 'register')->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/orders', OrderHistoryController::class)->name('orders.index');
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
});

Route::prefix('webhook')->name('webhook.')->middleware('throttle:120,1')->group(function () {
    Route::post('/duitku', DuitkuCallbackController::class)->name('duitku');
    Route::post('/melo', MeloCallbackController::class)->name('melo');
});

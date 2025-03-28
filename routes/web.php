<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;
use Illuminate\Support\Facades\Route;


// Redirect root to dashboard
Route::get('/', function (){
    return redirect()->route('dashboard');
});

// Dashboard routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

//Order routes
Route::get('/process-pending-orders', [OrderController::class, 'processPendingOrders'])
    ->name('orders.process-pending');

Route::get('retry-failed-orders', [OrderController::class, 'retryFailedOrders'])
    ->name('orders.process-failed');

//Paypal routes
Route::get('/payment/success', [PayPalController::class, 'handleSuccess'])
    ->name('payment.success');
Route::get('/payment/cancel', [PayPalController::class, 'handleCancel'])
    ->name('payment.cancel');

<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


// Redirect root to dashboard
Route::get('/', function (){
    return redirect()->route('dashboard');
});

// Dashboard routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

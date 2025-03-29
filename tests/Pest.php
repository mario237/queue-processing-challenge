<?php

use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
*/

uses()->group('order')->in('tests/Order');

// Automatically runs migrations before test suite
beforeEach(function () {
    Artisan::call('migrate:fresh');
})->group('order');

afterEach(function () {
    Artisan::call('migrate:rollback');
})->group('order');

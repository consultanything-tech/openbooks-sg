<?php

use App\Http\Controllers\Api\ApiBillController;
use App\Http\Controllers\Api\ApiCompanyController;
use App\Http\Controllers\Api\ApiCustomerController;
use App\Http\Controllers\Api\ApiInvoiceController;
use App\Http\Controllers\Api\ApiItemController;
use App\Http\Controllers\Api\ApiPaymentController;
use App\Http\Controllers\Api\ApiQuoteController;
use App\Http\Controllers\Api\ApiReportController;
use App\Http\Middleware\ApiAuthenticate;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api', ApiAuthenticate::class])->group(function () {
    // Invoices
    Route::get('/invoices', [ApiInvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [ApiInvoiceController::class, 'show']);
    Route::post('/invoices', [ApiInvoiceController::class, 'store']);
    Route::put('/invoices/{id}', [ApiInvoiceController::class, 'update']);
    Route::delete('/invoices/{id}', [ApiInvoiceController::class, 'destroy']);

    // Customers
    Route::get('/customers', [ApiCustomerController::class, 'index']);
    Route::get('/customers/{id}', [ApiCustomerController::class, 'show']);
    Route::post('/customers', [ApiCustomerController::class, 'store']);
    Route::put('/customers/{id}', [ApiCustomerController::class, 'update']);

    // Quotes
    Route::get('/quotes', [ApiQuoteController::class, 'index']);
    Route::get('/quotes/{id}', [ApiQuoteController::class, 'show']);
    Route::post('/quotes', [ApiQuoteController::class, 'store']);

    // Payments
    Route::get('/payments', [ApiPaymentController::class, 'index']);
    Route::post('/payments', [ApiPaymentController::class, 'store']);

    // Bills
    Route::get('/bills', [ApiBillController::class, 'index']);
    Route::get('/bills/{id}', [ApiBillController::class, 'show']);

    // Items
    Route::get('/items', [ApiItemController::class, 'index']);

    // Reports
    Route::get('/reports/profit-loss', [ApiReportController::class, 'profitLoss']);
    Route::get('/reports/balance-sheet', [ApiReportController::class, 'balanceSheet']);

    // Company
    Route::get('/company', [ApiCompanyController::class, 'show']);
});

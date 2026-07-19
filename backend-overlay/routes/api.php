<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuyerOrderController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CourierOrderController;
use App\Http\Controllers\Api\MerchantOrderController;
use App\Http\Controllers\Api\MerchantProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/catalog/stores', [CatalogController::class, 'stores']);
    Route::get('/catalog/products', [CatalogController::class, 'products']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('buyer')->group(function () {
            Route::get('/orders', [BuyerOrderController::class, 'index']);
            Route::post('/orders', [BuyerOrderController::class, 'store']);
            Route::get('/orders/{order}', [BuyerOrderController::class, 'show']);
            Route::post('/orders/{order}/cancel', [BuyerOrderController::class, 'cancel']);
        });

        Route::prefix('merchant')->group(function () {
            Route::get('/orders', [MerchantOrderController::class, 'index']);
            Route::post('/orders/{order}/status', [MerchantOrderController::class, 'updateStatus']);
            Route::get('/products', [MerchantProductController::class, 'index']);
            Route::post('/products', [MerchantProductController::class, 'store']);
            Route::put('/products/{product}', [MerchantProductController::class, 'update']);
        });

        Route::prefix('courier')->group(function () {
            Route::get('/jobs/available', [CourierOrderController::class, 'available']);
            Route::get('/jobs/mine', [CourierOrderController::class, 'mine']);
            Route::post('/jobs/{order}/claim', [CourierOrderController::class, 'claim']);
            Route::post('/jobs/{order}/status', [CourierOrderController::class, 'updateStatus']);
        });
    });
});

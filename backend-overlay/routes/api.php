<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\GatewayWebhookController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', DashboardController::class);

        Route::get('/devices', [DeviceController::class, 'index']);
        Route::post('/devices/session', [DeviceController::class, 'start']);
        Route::get('/devices/{session}/status', [DeviceController::class, 'status']);
        Route::get('/devices/{session}/qr', [DeviceController::class, 'qr']);
        Route::post('/devices/{session}/logout', [DeviceController::class, 'logout']);

        Route::post('/messages/text', [MessageController::class, 'sendText'])->middleware('throttle:30,1');
        Route::get('/messages', [MessageController::class, 'index']);
    });
});

Route::post('/gateway/webhook', GatewayWebhookController::class)->middleware('throttle:120,1');

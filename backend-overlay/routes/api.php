<?php

use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DispositionController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', DashboardController::class);

        Route::get('/units', [MasterDataController::class, 'units']);
        Route::get('/classifications', [MasterDataController::class, 'classifications']);
        Route::get('/locations', [MasterDataController::class, 'locations']);

        Route::get('/archives', [ArchiveController::class, 'index']);
        Route::post('/archives', [ArchiveController::class, 'store']);
        Route::get('/archives/{archive}', [ArchiveController::class, 'show']);
        Route::put('/archives/{archive}', [ArchiveController::class, 'update']);
        Route::post('/archives/{archive}/versions', [ArchiveController::class, 'uploadVersion']);
        Route::get('/archives/{archive}/download', [ArchiveController::class, 'download']);
        Route::delete('/archives/{archive}', [ArchiveController::class, 'destroy']);

        Route::get('/dispositions', [DispositionController::class, 'index']);
        Route::post('/dispositions', [DispositionController::class, 'store']);
        Route::patch('/dispositions/{disposition}/status', [DispositionController::class, 'updateStatus']);

        Route::get('/loans', [LoanController::class, 'index']);
        Route::post('/loans', [LoanController::class, 'store']);
        Route::patch('/loans/{loan}/approve', [LoanController::class, 'approve']);
        Route::patch('/loans/{loan}/return', [LoanController::class, 'markReturned']);
    });
});

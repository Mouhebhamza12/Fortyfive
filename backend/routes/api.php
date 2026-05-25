<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware(['trusted.origin', 'throttle:auth.register']);
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['trusted.origin', 'throttle:auth.login']);

    Route::middleware('auth.token')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile'])
            ->middleware('trusted.origin');
        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('trusted.origin');
    });
});

Route::get('/products', [\App\Http\Controllers\Admin\ProductController::class, 'index']);
Route::get('/products/{product:slug}', [\App\Http\Controllers\Admin\ProductController::class, 'show']);

Route::get('/shipping/options', [ShippingController::class, 'options']);
Route::get('/shipping/quote', [ShippingController::class, 'quote']);
Route::get('/wilayas', [ShippingController::class, 'wilayas']);
Route::get('/wilayas/{wilaya}/bureaus', [ShippingController::class, 'bureaus']);

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['auth.optional', 'trusted.origin', 'throttle:60,1']);

Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware(['auth.optional', 'trusted.origin', 'throttle:30,1']);

Route::middleware('auth.token')->group(function (): void {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
});

Route::prefix('admin')->middleware(['auth.token', 'admin'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);

    Route::post('/products/upload-image', [\App\Http\Controllers\Admin\ProductImageController::class, 'store']);
    Route::apiResource('products', \App\Http\Controllers\Admin\ProductController::class);
    Route::apiResource('orders', \App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::get('/feedback', [\App\Http\Controllers\Admin\FeedbackController::class, 'index']);
    Route::put('/feedback/{feedback}', [\App\Http\Controllers\Admin\FeedbackController::class, 'update']);
    Route::delete('/feedback/{feedback}', [\App\Http\Controllers\Admin\FeedbackController::class, 'destroy']);
});

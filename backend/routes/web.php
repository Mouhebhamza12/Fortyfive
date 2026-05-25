<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Cookie\Middleware\EncryptCookies;

Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])
    ->withoutMiddleware([EncryptCookies::class])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])
    ->withoutMiddleware([EncryptCookies::class])
    ->middleware(['guest', 'throttle:20,1'])
    ->name('google.callback');

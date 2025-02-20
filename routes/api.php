<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\MenuPriceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyEmailController;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::put('payment/{Pid}', [OrderController::class, 'updateStatus']);

Route::middleware('jwt.auth')->group(function () {
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('email/change-password', [AuthController::class, 'changePassword']);


    Route::prefix('user')->group(function () {
        Route::get('/{user}', [UserController::class, 'show']);
        Route::get('/', [UserController::class, 'getall']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });


    Route::prefix('vendor')->group(function () {
        Route::get('/{vendor}', [VendorController::class, 'show']);
        Route::get('/', [VendorController::class, 'index']);
        Route::post('/', [VendorController::class, 'store']);
        Route::put('/{vendor}', [VendorController::class, 'update']);
        Route::delete('/{vendor}', [VendorController::class, 'destroy']);
    });

    Route::prefix('order')->group(function () {
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::delete('/{order}', [OrderController::class, 'delete']);
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::put('/order-status/{order}', [OrderController::class, 'orderStatus']);
    });

    Route::prefix('menu-item')->group(function () {
        Route::get('/', [MenuItemController::class, 'index']);
        Route::post('/', [MenuItemController::class, 'store']);
        Route::get('/{menuItem}', [MenuItemController::class, 'show']);
        Route::post('/{menuItem}', [MenuItemController::class, 'update']);
        Route::delete('/{menuItem}', [MenuItemController::class, 'destroy']);
    });

    Route::prefix('menu-price')->group(function () {
        Route::get('/{menuPrice}', [MenuPriceController::class, 'show']);
        Route::get('/', [MenuPriceController::class, 'index']);
        Route::post('/', [MenuPriceController::class, 'store']);
    });
});
Route::post('email/resend', [VerifyEmailController::class, 'resend'])
    ->middleware('throttle:6,1');
Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])->name('verification.verify');

Route::get('test/role', function () {
    return response()->json(['message' => 'You have role editor']);
})->middleware(['jwt.auth', 'role:editor']);


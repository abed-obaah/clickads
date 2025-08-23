<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BankDetailController;
use App\Http\Controllers\BulkTransferController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
Route::post('/resend-otp', [OtpController::class, 'resendOtp']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Public store routes
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);
Route::get('/stores/{storeId}/products', [ProductController::class, 'index']);
Route::get('/stores/{storeId}/products/{productId}', [ProductController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
    
    // Wallet routes
    Route::get('/wallet/balance', [WalletController::class, 'getBalance']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactionHistory']);
    Route::get('/wallet/notifications', [WalletController::class, 'notifications']);
    Route::post('/wallet/notifications/{id}/read', [WalletController::class, 'markNotificationAsRead']);
    Route::post('/wallet/notifications/read-all', [WalletController::class, 'markAllNotificationsAsRead']);
    
    // Store management routes
    Route::get('/user/stores', [StoreController::class, 'userStores']);
    Route::post('/stores', [StoreController::class, 'store']);
    Route::put('/stores/{id}', [StoreController::class, 'update']);
    Route::delete('/stores/{id}', [StoreController::class, 'destroy']);
    
    // Product management routes
    Route::post('/stores/{storeId}/products', [ProductController::class, 'store']);
    Route::put('/stores/{storeId}/products/{productId}', [ProductController::class, 'update']);
    Route::delete('/stores/{storeId}/products/{productId}', [ProductController::class, 'destroy']);
    
    // Bank details routes
    Route::get('/bank-details', [BankDetailController::class, 'index']);
    Route::get('/bank-details/{id}', [BankDetailController::class, 'show']);
    Route::post('/bank-details', [BankDetailController::class, 'store']);
    Route::put('/bank-details/{id}', [BankDetailController::class, 'update']);
    Route::delete('/bank-details/{id}', [BankDetailController::class, 'destroy']);
    Route::post('/bank-details/{id}/set-default', [BankDetailController::class, 'setDefault']);
    Route::post('/bank-details/verify', [BankDetailController::class, 'verifyAccount']);

     Route::post('/bulk-transfers/initiate', [BulkTransferController::class, 'initiateBulkTransfer']);
    Route::post('/bulk-transfers/calculate', [BulkTransferController::class, 'calculateTotal']);
    Route::get('/bulk-transfers', [BulkTransferController::class, 'bulkTransferHistory']);
    Route::get('/bulk-transfers/{id}', [BulkTransferController::class, 'bulkTransferDetails']);
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\FacebookOAuthController;
use App\Http\Controllers\Api\Admin\FacebookOAuthController as AdminFacebookOAuthController;
use App\Http\Controllers\Api\InstagramController;


Route::post('/login',[AuthController::class,'apiLogin']);
Route::post('/register',[AuthController::class,'apiRegister']);
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API connected',
    ]);
});
Route::post('/post-instagram', [InstagramController::class, 'post']);

Route::middleware('auth:api')->group(function(){

    Route::post('/logout',[AuthController::class,'apiLogout']);
    
    // User OAuth routes
    Route::get('/facebook/connect-url', [FacebookOAuthController::class, 'getConnectUrl']);
    Route::post('/facebook/callback', [FacebookOAuthController::class, 'handleCallback']);
    Route::post('/facebook/save-page', [FacebookOAuthController::class, 'savePage']);
    
});
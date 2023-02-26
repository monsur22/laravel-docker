<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group(['middleware' => 'api','prefix'=>'auth'], function ($router) {
    Route::post('/register', [AuthController::class, 'createUser']);
    Route::post('/login', [AuthController::class, 'loginUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/email-verify/{confirm_code}', [AuthController::class, 'verifyRegistration']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/reset-password/{confirm_code}', [AuthController::class, 'updatePassword']);

    /***    This  route will be for view password update form  ***
    Route::get('/reset-password/{confirm_code}', [AuthController::class, 'getViewPage']);
    ***    This  route will be for view password update form     ******/
});


Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('/user', [AuthController::class, 'user']);
});

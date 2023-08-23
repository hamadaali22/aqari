<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['middleware' => ['api'], 'namespace' => 'Api'], function () {
    Route::get('/admin', function () {
        return view('welcome');
    });
    // Route::post('verify-code', [AuthLoginController::class, 'verifyCode'])->name('verify-code');
       Route::post('login', 'AuthLoginController@LoginUser')->name('login-user');


    Route::post('verify-register-code', 'AuthLoginController@verifyRegisterCode')->name('verify-register-code');
    Route::post('register', 'AuthLoginController@registerNewUser')->name('user-signup');

    Route::post('forget-password', 'AuthLoginController@forgetPassword');
});

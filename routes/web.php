<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function (){
    Route::get('businessList','ApisController@businessList')->name('businessList');

    Route::get('business','ApisController@business')->name('business');
    //手机验证码
    Route::get('sms','ApisController@sms')->name('sms');
    //注册
    Route::post('regist','ApisController@regist')->name('regist');
    //登录
    Route::post('loginCheck','ApisController@loginCheck')->name('loginCheck');

});



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

    Route::post('regist','ApisController@regist')->name('regist');

});



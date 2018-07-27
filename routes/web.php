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
    //�ֻ���֤��
    Route::get('sms','ApisController@sms')->name('sms');
    //ע��
    Route::post('regist','ApisController@regist')->name('regist');
    //��¼
    Route::post('loginCheck','ApisController@loginCheck')->name('loginCheck');
    // 地址列表接口
    Route::get('addressList','ApisController@addressList')->name('addressList');
    //添加地址
    Route::post('addAddress','ApisController@addAddress')->name('addAddress');
    //指定一条地址
    Route::get('address','ApisController@address')->name('address');
    //修改地址并保存
    Route::post('editAddress','ApisController@editAddress')->name('editAddress');
    // 保存菜品到购物车
    Route::post('addCart','ApisController@addCart')->name('addCart');
    //获取购物车数据
    Route::get('cart','ApisController@cart')->name('cart');
    //生成订单
    Route::post('addorder','ApisController@addorder')->name('addorder');
    //获得指定订单
    Route::get('order','ApisController@order')->name('order');
    //支付
    Route::post('pay','ApisController@pay')->name('pay');
    //获得订单列表
    Route::get('orderList','ApisController@orderList')->name('orderList');
    //修改密码
    Route::post('changePassword','ApisController@changePassword')->name('changePassword');
    // 忘记密码
    Route::post('forgetPassword','ApisController@forgetPassword')->name('forgetPassword');
});



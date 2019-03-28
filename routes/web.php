<?php

Route::get('/', 'HomeController@index');
Route::get('/index', 'HomeController@index');
Route::get('/home', 'HomeController@index');

// 		登入/登出/忘記密碼

// Auth::routes();

Route::post('/login', ['as'=>'login','uses'=>'Auth\LoginController@login']);
Route::post('/logout', ['as'=>'login','uses'=>'Auth\LoginController@logout']);
Route::post('/password/email', ['as'=>'ForgetPassword','uses'=>'Auth\ForgotPasswordController@sendResetLinkEmail']);


// 		帳號 CRUD

Route::resource('/user', 'UserController')->middleware('token.verify');

// 		驗證mail是否存在

Route::post('/check_mail_exist', ['as'=>'check_mail_exist','uses'=>'UserController@check_mail_exist']);

// 		密碼重置(忘記密碼)

Route::post('/pwd_reset', ['as'=>'pwd_reset','uses'=>'UserController@pwd_reset']);

// 		到期檢查

Route::get('/is_expire/{id}', ['as'=>'is_expire','uses'=>'UserController@is_expire'])->middleware('token.verify');

// 		帳號 CRUD

Route::resource('/user', 'UserController')->middleware('token.verify');

// 		服務 CRUD

Route::get('/service', ['as'=>'service','uses'=>'ServiceController@index'])->middleware('token.verify');


// 		股票	CRUD

Route::resource('/stock', 'StockController');
// Route::resource('/stock', 'StockController')->middleware('token.verify');


//		data 		

Route::get('/get_data', 'SellBuyPercentController@get_data');
Route::get('/count_data', 'SellBuyPercentController@count_data');
Route::get('/get_buy_sell_report', 'SellBuyPercentController@get_buy_sell_report');
Route::get('/get_stock_list', 'SellBuyPercentController@get_stock_list');
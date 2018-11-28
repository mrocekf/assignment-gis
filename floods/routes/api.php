<?php

use Illuminate\Http\Request;

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

Route::get('/floods-for-hospital', 'MapsController@floodsForHospital');
Route::get('/hospitals/{cityId}', 'MapsController@hospitals');
Route::get('/floods/{cityId}', 'MapsController@floods');
Route::get('/cities', 'MapsController@cities');
Route::get('/closest-safe-hospital', 'MapsController@closestSafeHospital');

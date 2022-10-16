<?php

use App\Http\Controllers\ManagerLiveStreamController;
use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Route;

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

Route::get('/socket-check', function () {
    return view('socket_connect');
});

Route::get('/checkout-session/success', [StripeController::class, 'successCheckoutStripe']);
Route::get('/checkout-session/cancel', [StripeController::class, 'cancelCheckoutStripe']);
Route::get('download/livestream/{id}', [ManagerLiveStreamController::class, 'downloadLivestream']);

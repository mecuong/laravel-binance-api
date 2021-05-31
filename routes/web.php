<?php

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

Route::get('/exchangeInfo', function () {
    $api = new \App\Utils\BinanceApi\FetchApi;
    return $api->get('/api/v3/exchangeInfo', function($api) {
        $api->unSignature();
    });
});

Route::get('/spot-info', function () {
    $api = new \App\Utils\BinanceApi\FetchApi;

    return $api->setUrl('/api/v3/account')
    ->setMethod('get')
    ->setTimetamp(null)
    ->exec();
});


Route::get('/prices', function () {
    $api = new \App\Utils\BinanceApi\FetchApi;
    $api->runWebsocket('ALL_TICKERS', [], function ($msg) {
        $msg_parse = new \App\Utils\BinanceApi\WebsocketMessageParser($msg);
        $prices    = \Illuminate\Support\Arr::pluck($msg_parse->toArray(), 'c', 's');

        // It's real time please use event here
        echo $prices['DOGEUSDT'] . '<br />';
    });
});


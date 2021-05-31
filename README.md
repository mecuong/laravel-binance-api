<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Run
- Run `composer install`

- Please add your Binance API key and secret to file `.env`
```code
    BINANCE_API_KEY=xxxxx
    BINANCE_API_SECRET=xxxxx
```

- Run laravel server `php artisan serve --port=8000`

- Open file `./routes/web.php` to read and run to test

- Read file `./app/utils/binanceapi/fetchapi.php` to know and extends

- More API please read Binance document <https://binance-docs.github.io/apidocs/spot/en/#general-info>

### Create coin order example
```code
    /**
     * symbol is pair for trade example BTCUSDT
     * price for buy or sell
     * quantity want to buy or sell
     * Side variable is BUY or SELL
     *
     * Type	Additional mandatory parameters
     *   LIMIT	timeInForce, quantity, price
     *   MARKET	quantity or quoteOrderQty
     *   STOP_LOSS	quantity, stopPrice
     *   STOP_LOSS_LIMIT	timeInForce, quantity, price, stopPrice
     *   TAKE_PROFIT	quantity, stopPrice
     *   TAKE_PROFIT_LIMIT	timeInForce, quantity, price, stopPrice
     *   LIMIT_MAKER	quantity, price
     */
    public function order($symbol, $price, $quantity, $side = self::TRADE_TYPE_BUY, $type = 'MARKET')
    {
        $api = new \App\Utils\BinanceApi\FetchApi;
        $api->setMethod('post')
        ->setUrl('/api/v3/order')
        ->clearParams()
        ->setParams('symbol', $symbol)
        ->setParams('type', strtoupper($type))
        ->setParams('side', strtoupper($side))
        ->setParams('price', $price)
        ->setParams('quantity', $quantity)
        ->setTimetamp(null);

        if ($type == 'MARKET') {
            $this->api->unsetParam('price');
        }

        try {
            return $this->api->exec();
        } catch(Exception $e) {
            // dd($api->getResponse()->body());
            return false;
        }
    }
```

# Thanks and good luck for trade

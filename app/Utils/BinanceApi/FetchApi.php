<?php

namespace App\Utils\BinanceApi;

use Illuminate\Support\Facades\Http;
use App\Utils\BinanceApi\ApiException;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;

class FetchApi {

    const BINANCEA_API_LIST     = ["https://api1.binance.com","https://api2.binance.com","https://api3.binance.com",];
    const LIST_METHOD_ALLOWED   = ['get', 'post', 'put', 'patch', 'delete'];
    const BINANCE_WEBSOCKET_URL = 'wss://stream.binance.com:9443/ws/';

    private $_key;
    private $_secret;
    private $_url;
    private $_method = 'get';
    private $_headers = [];
    private $_params = [];
    private $_timeout = 60;
    private $_timeOffset = 0;
    private $_response;
    private $_callback;
    private $_signature = true;
    private $_socketUrl;
    private $_socketCallback;
    private $_closeWebsocketCallback;
    private $_errorWebsocketCallback;

    public function __construct($apiKey = null, $apiSecret = null)
    {
        $this->_key    = $apiKey ?? env('BINANCE_API_KEY');
        $this->_secret = $apiSecret ?? env('BINANCE_API_SECRET');
        $this->setHeaders('X-MBX-APIKEY', $this->_key);
    }

    public function exec()
    {
        $http = Http::withHeaders($this->_headers)->timeout($this->_timeout);

        if ($this->_signature) {
            $this->signature();
        }

        if ($this->_method == 'get') {
            $this->_response = $this->getMethodGetClient($http);
        } else {
            $this->_response = $http->asForm()->{$this->_method}($this->_url, $this->_params);
        }

        if (!$this->_response->successful()) {
            if ($this->_response->status() >= 200 && $this->_response->status() < 299) {
                throw new ApiException($this->_response->json());
            }

            throw new ApiException(['code' => $this->_response->status(), 'msg' => $this->_response->getReasonPhrase() ]);
        }

        if (is_callable($this->_callback)) {
            return call_user_func($this->_callback, $this->parseResponse());
        }

        return $this->parseResponse();
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function setUrl($url)
    {
        $this->_url = $this->_getApiUrl($url);

        return $this;
    }

    public function unSignature()
    {
        $this->_signature = false;

        return $this;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function setMethod($method = 'get')
    {
        $method = strtolower($method);

        if (!in_array($method, self::LIST_METHOD_ALLOWED)) {
            throw new ApiException(['message' => '405 Method Not Allowed', 'code' => 405]);
        }

        $this->_method = $method;

        return $this;
    }

    public function setParams($key, $value = null)
    {
        if (is_array($key) && !$value) {
            $this->_params += $key;
        } else if ($key && $value) {
            $this->_params[$key] = $value;
        }

        return $this;
    }

    public function unsetParam($key)
    {
        if (isset($this->_params[$key])) {
            unset($this->_params[$key]);
        }

        return $this;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getParam($name, $default = null)
    {
        return $this->_params[$name] ?? $default;
    }

    public function clearParams()
    {
        $this->_params = [];

        return $this;
    }

    public function setTimeout($time = 60)
    {
        $this->timeout = $time;

        return $this;
    }

    public function setHeaders($key, $value = null)
    {
        if (is_array($key) && !$value) {
            $this->_headers += $key;
        } else if ($key && $value) {
            $this->_headers[$key] = $value;
        }

        return $this;
    }

    public function setCallBack($callback)
    {
        $this->_callback = $callback;
    }

    public function setTimetamp($recvWindow = 1000)
    {
        $timestamp = time() * 1000 + $this->_timeOffset;
        $this->setParams('timestamp', $timestamp);

        if ($recvWindow) {
            $this->setParams('recvWindow', $recvWindow);
        }

        return $this;
    }

    public function setTimeOffset($time)
    {
        $this->_timeOffset = $time;
    }

    /**
     * Method get API
     *
     * @param string $url Set URL wanna get
     * @param mixed  $params Set request params or callback function
     * @param callable $response response callback function
     *
     * @return \Illuminate\Support\Collection
     */
    public function get($url, $params = [], $response = null)
    {
        return $this->restMethodExec(__FUNCTION__, [$url, $params, $response]);
    }

    /**
     * Method Post API
     *
     * @param string $url Set URL wanna POST
     * @param mixed  $params Set request params or callback function
     * @param callable $response response callback function
     *
     * @return \Illuminate\Support\Collection
     */
    public function post($url, $params = [], $response = null)
    {
        return $this->restMethodExec(__FUNCTION__, [$url, $params, $response]);
    }

    /**
     * Opens a websocket connection and transmits received messages until closed.
     *
     * @param string $type   The websocket method.
     * @param array  $params The parameters to send.
     * @return void
     * @throws Exception
     */

    public function runWebsocket($type, $params, $callback = null)
    {
        $websocket  = self::BINANCE_WEBSOCKET_URL;

        if (isset($params['symbol'])) {
            $websocket .= strtolower($params['symbol']);
        }

        switch (strtoupper($type)) {
            default:
            case 'DEPTH':
                $websocket  .= '@depth';
            break;
            case 'KLINE':
                $websocket  .= '@kline_' . $params['interval'];
            break;
            case 'TRADES':
                $websocket  .= '@aggTrade';
            break;
            case 'USER':
            break;
            case 'ALL_TICKERS':
                $websocket  .= '!ticker@arr';
            break;
        }

        $this->_socketUrl = $websocket;

        if (is_callable($callback)) {
            $this->setSocketCallback($callback);
        }

        $this->execWebsocket();
    }

    public function setCloseWebsocketCallback($callback)
    {
        $this->_closeWebsocketCallback = $callback;
    }

    public function getCloseWebsocketCallback()
    {
        return $this->_closeWebsocketCallback;
    }

    public function setErrorWebsocketCallback($callback)
    {
        $this->_errorWebsocketCallback = $callback;
    }

    public function getErrorWebsocketCallback()
    {
        return $this->_errorWebsocketCallback;
    }

    private function execWebsocket()
    {
        \Ratchet\Client\connect($this->_socketUrl)->then(function (WebSocket $conn) {
            $conn->on('message', function (MessageInterface $msg) use ($conn) {
                call_user_func($this->getSocketCallback(), $msg, $conn);
            });

            $conn->on('close', function ($code = null, $reason = null){
                echo 'Connection closed (' . $code . ' - ' . $reason . ')' . PHP_EOL;
                if (is_callable($this->getCloseWebsocketCallback())) {
                    call_user_func($this->getCloseWebsocketCallback(), $code, $reason);
                }
                $this->execWebsocket();
            });

            $conn->on('error', function () {
                $this->_log('[ERROR|Websocket] Could not establish a connection. Restart Connection ...');
                if (is_callable($this->getErrorWebsocketCallback())) {
                    call_user_func($this->getErrorWebsocketCallback());
                }
                $this->execWebsocket();
            });
        });
    }

    private function setSocketCallback($callback)
    {
        if (is_callable($callback)) {
            $this->_socketCallback = $callback;
        }
    }

    private function getSocketCallback()
    {
        return $this->_socketCallback;
    }

    private function _getApiUrl($url)
    {
        $apiPosition = collect(self::BINANCEA_API_LIST);
        $url         = ltrim($url, '/');
        $apiUrl      = $apiPosition->random() . "/{$url}";

        return $apiUrl;
    }

    private function signature()
    {
        $this->_params[__FUNCTION__] = hash_hmac('sha256',  http_build_query($this->_params), $this->_secret) ;
    }

    private function getMethodGetClient($http)
    {
        $url = $this->_url . '?' . http_build_query((array) $this->_params);
        return $http->get($url);
    }

    private function parseResponse()
    {
        $result = @json_decode($this->_response->body());
        return collect($result);
    }

    /**
     * For RestFul API standard with
     * argument 0 is url
     * argument 1 is params
     * argument 2 is callback
     * argument 3 is header
     */
    private function restMethodExec($name, $arguments)
    {
        $this->setMethod($name);
        $this->setUrl($arguments[0] ?? '');

        // Set more for request
        if (is_callable($arguments[1] ?? [])) {
            call_user_func($arguments[1], $this);
        } else {
            $this->setParams($arguments[1] ?? []);
        }

        if (isset($arguments[2]) && is_callable($arguments[2])) {
            $this->setCallBack($arguments[2]);
        }

        $this->setHeaders($arguments[3] ?? []);

        return $this->exec();
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, self::LIST_METHOD_ALLOWED)) {
            return $this->restMethodExec($name, $arguments);
        }
    }
}

<?php

namespace App\Utils\BinanceApi;

use Ratchet\RFC6455\Messaging\MessageInterface;

class WebsocketMessageParser {

    private $_message;

    public function __construct(MessageInterface $message)
    {
        $this->_message = $message;
    }

    public function __toString() : string
    {
        return $this->_message->__toString();
    }

    public function toArray() : array
    {
        return (array) @json_decode($this->__toString(), true);
    }

    public function toJson() : string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
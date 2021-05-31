<?php

namespace App\Utils\BinanceApi;

/**
 * Class ApiException
 */
class ApiException extends \Exception
{
    /**
     * Error code.
     *
     * @var int
     */
    protected $code;

    /**
     * Error message.
     *
     * @var string
     */
    protected $message;

    /**
     * BinanceApiException constructor.
     *
     * @param string          $message  The exception message.
     * @param int             $code     The exception code.
     * @param \Exception|null $previous The previous exceptions.
     */
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        $this->_decodeBinanceException($message);
        parent::__construct($this->message, $this->code, $previous);
    }

    /**
     * String representation of the exception.
     *
     * @return string
     */
    public function __toString()
    {
        return "[{$this->code}]: {$this->message} \n";
    }

    /**
     * Decodes received exception message.
     *
     * @param string $message The exception message.
     */
    private function _decodeBinanceException($message)
    {
        if (!is_array($message)) {
            $message = @json_decode($message);
        }

        $this->code = $message['code'] ?? 0;
        $this->message = $message['msg'] ?? '';
    }
}
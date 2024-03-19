<?php

namespace App\Exception;

use Exception;

class CurrencyFailedException extends Exception
{
    public function __construct($message = "Failed to fetch currency rates", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Предоставьте дополнительные данные для логирования.
     *
     * @return array
     */
    public function context(): array
    {
        return [
            'service' => 'CBR',
        ];
    }
}

<?php

namespace Ermine\Exceptions;

use Exception;
use Throwable;

class Error404Exception extends Exception
{

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @todo : change view to 404
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        header('HTTP/1.0 404 Not Found');
        echo '<h1>404 Not Found</h1>';
        parent::__construct($message, $code, $previous);
    }

}

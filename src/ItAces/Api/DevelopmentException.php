<?php

namespace ItAces\Api;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;

class DevelopmentException extends Exception implements HttpExceptionInterface
{
    
    protected $status = 400;
    
    public function __construct($message = null, $code = null, $previous = null, $status = 400) {
        parent::__construct($message, $code, $previous);
        $this->status = $status;
    }
    
    public function getStatusCode()
    {
        return $this->status;
    }
    
    public function setStatusCode(int $status)
    {
        $this->status = $status;
    }

    public function getHeaders()
    {
        return [];
    }

}

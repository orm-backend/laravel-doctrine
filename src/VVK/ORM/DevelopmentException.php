<?php

namespace VVK\ORM;

use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class DevelopmentException extends Exception implements HttpExceptionInterface, Responsable
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Support\Responsable::toResponse()
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'status' => $this->status,
                'message' => $this->message
            ], $this->status);
        }
        
        if (config('app.debug')) {
            throw new Exception($this->message);
        }
        
        throw new HttpException($this->status);
    }

}

<?php

namespace OrmBackend\Traits;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
trait Authenticatable
{
    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Auth\Authenticatable::getAuthIdentifierName()
     */
    public function getAuthIdentifierName()
    {
        return self::getIdentifierName();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Auth\Authenticatable::getRememberTokenName()
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Auth\Authenticatable::getAuthIdentifier()
     */
    public function getAuthIdentifier()
    {
        return $this->getPrimary();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Illuminate\Contracts\Auth\Authenticatable::getAuthPassword()
     */
    public function getAuthPassword()
    {
        return $this->getPassword();
    }

}

<?php

namespace App\Model;

use ItAces\SoftDeleteable;
use ItAces\Publishable;


class Role extends \ItAces\ORM\Entities\Role implements SoftDeleteable, Publishable
{
    /**
     *
     * {@inheritDoc}
     * @see \ItAces\ORM\Entities\EntityBase::getModelValidationRules()
     */
    public function getModelValidationRules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:App\Model\Role,code,'.$this->getId()]
        ];
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \ItAces\ORM\Entities\EntityBase::getRequestValidationRules()
     */
    static public function getRequestValidationRules()
    {
        return [];
    }

}

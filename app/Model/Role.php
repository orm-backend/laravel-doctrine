<?php

namespace App\Model;

use VVK\SoftDeleteable;
use VVK\Publishable;


class Role extends \VVK\ORM\Entities\Role implements SoftDeleteable, Publishable
{
    /**
     *
     * {@inheritDoc}
     * @see \VVK\ORM\Entities\Entity::getModelValidationRules()
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
     * @see \VVK\ORM\Entities\Entity::getRequestValidationRules()
     */
    static public function getRequestValidationRules()
    {
        return [];
    }

}

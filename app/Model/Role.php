<?php

namespace App\Model;

use OrmBackend\SoftDeleteable;
use OrmBackend\Publishable;


class Role extends \OrmBackend\ORM\Entities\Role implements SoftDeleteable, Publishable
{
    /**
     *
     * {@inheritDoc}
     * @see \OrmBackend\ORM\Entities\Entity::getModelValidationRules()
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
     * @see \OrmBackend\ORM\Entities\Entity::getRequestValidationRules()
     */
    static public function getRequestValidationRules()
    {
        return [];
    }

}

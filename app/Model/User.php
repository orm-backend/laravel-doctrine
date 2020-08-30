<?php

namespace App\Model;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use VVK\SoftDeleteable;
use VVK\Publishable;

class User extends \VVK\ORM\Entities\User
implements Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail, SoftDeleteable, Publishable
{
    use \Illuminate\Auth\Passwords\CanResetPassword;
    use \Illuminate\Foundation\Auth\Access\Authorizable;
    use \VVK\Traits\Notifiable;
    use \VVK\Traits\MustVerifyEmail;
    use \VVK\Traits\Authenticatable;
    use \VVK\Traits\UsesPasswordGrant;
    use \Laravel\Passport\HasApiTokens;
    
    /**
     * Fields to be excluded from the response.
     *
     * @var string[]
     */
    public static $hidden = ['password', 'rememberToken'];
    
    /**
     *
     * {@inheritDoc}
     * @see \VVK\ORM\Entities\Entity::getModelValidationRules()
     */
    public function getModelValidationRules()
    {
        return [
            // Does not allow guests to read users. An exception will be thrown if the email is not unique.
            //'email' => ['required', 'string', 'max:255', 'email:rfc,dns', 'unique:App\Model\User,email,'.$this->getId()],
            'email' => ['required', 'string', 'max:255', 'email:rfc,dns'],
            'roles' => ['required', 'persistentcollection:App\Model\Role,1']
        ];
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \VVK\ORM\Entities\Entity::getRequestValidationRules()
     */
    static public function getRequestValidationRules()
    {
        return [
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'roles' => ['sometimes', 'nullable', 'arrayofinteger', 'exists:App\Model\Role,id']
        ];
    }

    /**
     * 
     * @param string $code
     * @return bool
     */
    public function hasRole(string $code) : bool
    {
        return $this->getRoles()->exists(function(int $i, Role $role) use($code) {
            return $role->getCode() === $code;
        });
    }

    /**
     * 
     * @return string
     */
    public function getName() {
        return $this->email;
    }
}

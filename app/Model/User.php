<?php
namespace App\Model;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use ItAces\UnderAdminControl;

class User extends \ItAces\ORM\Entities\User
implements Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail, UnderAdminControl
{
    use \Illuminate\Auth\Passwords\CanResetPassword;
    use \Illuminate\Foundation\Auth\Access\Authorizable;
    use \ItAces\Traits\Notifiable;
    use \ItAces\Traits\MustVerifyEmail;
    use \ItAces\Traits\Authenticatable;
    
    /**
     * Fields to be excluded from the JSON response.
     *
     * @var string[]
     */
    public static $hidden = ['password', 'rememberToken'];
    
    /**
     *
     * {@inheritDoc}
     * @see \ItAces\ORM\Entities\EntityBase::getModelValidationRules()
     */
    public function getModelValidationRules()
    {
        return [
            'email' => ['required', 'string', 'max:255', 'email:rfc,dns', 'unique:App\Model\User,email,'.$this->getId()],
            'roles' => ['required', 'persistentcollection:App\Model\Role,1']
        ];
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \ItAces\ORM\Entities\EntityBase::getRequestValidationRules()
     */
    static public function getRequestValidationRules()
    {
        return [
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'roles' => ['sometimes', 'nullable', 'arrayofinteger:1', 'exists:App\Model\Role,id']
        ];
    }

}

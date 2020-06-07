<?php

namespace ItAces\ORM\Entities;

abstract class User extends \ItAces\ORM\Entities\BaseEntity
{

    /**
     * @var int
     */
    protected $id;
    
    /**
     * @var string
     */
    protected $email;

    /**
     * @var \Carbon\Carbon|null
     */
    protected $emailVerifiedAt;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string|null
     */
    protected $rememberToken;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $roles;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set emailVerifiedAt.
     *
     * @param \Carbon\Carbon|null $emailVerifiedAt
     *
     * @return \ItAces\ORM\Entities\User
     */
    public function setEmailVerifiedAt($emailVerifiedAt = null)
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    /**
     * Get emailVerifiedAt.
     *
     * @return \Carbon\Carbon|null
     */
    public function getEmailVerifiedAt()
    {
        return $this->emailVerifiedAt;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return \ItAces\ORM\Entities\User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set rememberToken.
     *
     * @param string|null $rememberToken
     *
     * @return \ItAces\ORM\Entities\User
     */
    public function setRememberToken($rememberToken = null)
    {
        $this->rememberToken = $rememberToken;

        return $this;
    }

    /**
     * Get rememberToken.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return $this->rememberToken;
    }
    
    /**
     * Add role.
     *
     * @param \ItAces\ORM\Entities\Role $role
     *
     * @return User
     */
    public function addRole(Role $role)
    {
        $this->roles->add($role);

        return $this;
    }

    /**
     * Remove role.
     *
     * @param \ItAces\ORM\Entities\Role $role
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRole(Role $role)
    {
        return $this->roles->removeElement($role);
    }

    /**
     * Get roles.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

}

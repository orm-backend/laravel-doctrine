<?php
namespace OrmBackend\Traits;

trait UsesPasswordGrant
{
    
    /**
     * @param string $userIdentifier
     * @return \App\Model\User
     */
    public function findForPassport($userIdentifier)
    {
        /**
         *
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = app('em');
        
        return $em->getRepository(static::class)->findOneBy(['email' => $userIdentifier]);
    }
    
}

<?php
namespace OrmBackend\Listener;

use OrmBackend\SoftDeleteable;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Illuminate\Support\Facades\Auth;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class DoctrineListener
{
    public function preFlush(PreFlushEventArgs $event) {
        $em = $event->getEntityManager();
        
        foreach ($em->getUnitOfWork()->getScheduledEntityDeletions() as $object) {
            if ($object instanceof SoftDeleteable) {
                $object->setDeletedAt(now());
                
                if (Auth::id()) {
                    $object->setDeletedBy(Auth::user());
                }
                
                $em->persist($object);
            }
        }
    }
}

<?php
namespace ItAces\Listener;

use ItAces\SoftDeleteable;
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
                $object->setDeletedBy(Auth::user());
                $em->persist($object);
            }
        }
    }
}

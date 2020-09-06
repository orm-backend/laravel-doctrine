<?php
namespace OrmBackend\Listener;

use OrmBackend\SoftDeleteable;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class DoctrineListener
{
    public function preFlush(PreFlushEventArgs $event) {
        if (env('DEMO', false) && Auth::id() != 1) {
            throw ValidationException::withMessages(['Data modification is not available in demo mode. This interrupt is triggered from the Doctrine Session PreFlush event.']);
        }
        
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

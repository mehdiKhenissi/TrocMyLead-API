<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class PasswordSubscriber implements EventSubscriberInterface
{
    
    public function __construct(private UserPasswordHasherInterface $userPasswordHasherInterface) {
        
    }
    
//    public function onKernelView(ViewEvent $event): void
//    {
//        die('ok');
//    }
    
    public function hashPassword(ViewEvent $event): void
    {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        
        if( !$entity instanceof  User  ||  $method !== Request::METHOD_POST ){
            return;
        }
        
        $hashedPassword = $this->userPasswordHasherInterface->hashPassword(
                $entity,
                $entity->getPassword()
        );
        
        $entity->setPassword($hashedPassword);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW =>['hashPassword', EventPriorities::PRE_WRITE ],
        ];
    }
}

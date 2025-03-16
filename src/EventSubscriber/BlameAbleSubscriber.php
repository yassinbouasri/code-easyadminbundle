<?php

namespace App\EventSubscriber;

use App\Entity\Question;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\Security\Core\Security;

class BlameAbleSubscriber implements EventSubscriberInterface
{
    public function __construct(public Security $security)
    {
    }

    public function onBeforeEntityUpdatedEvent(BeforeEntityUpdatedEvent $event)
    {
        //Or override updateEntity() in the CrudController
        $question = $event->getEntityInstance();

        if (!$question instanceof Question){
            return;
        }
        $user = $this->security->getUser();

        if (!$user instanceof User){
            throw new \LogicException('User should be an instance of User');
        }

        $question->setUpdatedBy($user);
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityUpdatedEvent::class => 'onBeforeEntityUpdatedEvent',
        ];
    }
}

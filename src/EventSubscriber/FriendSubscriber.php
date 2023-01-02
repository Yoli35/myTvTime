<?php

namespace App\EventSubscriber;

use App\Controller\UserController;
use App\Entity\User;
use App\Repository\FriendRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\User\UserInterface;

class FriendSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UserController $userController)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(ControllerEvent $event)
    {
        $this->userController->checkPendingFriendRequest();
    }
}
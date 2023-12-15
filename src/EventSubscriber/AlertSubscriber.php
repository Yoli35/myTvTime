<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\AlertService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class AlertSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AlertService    $alertService,
        private Security        $security,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'checkUserAlertsOfTheDay',
        ];
    }

    public function checkUserAlertsOfTheDay(ControllerEvent $event): void
    {
        if ($event->isMainRequest()) {
            /** @var User $user */
            $user = $this->security->getUser();

            $controller = $event->getController();

            if (gettype($controller) == "array" && $user && $controller[1] !== 'chatUpdate' && $controller[1] !== 'chatDiscussionUpdate') {
//                $this->alertService->checkUserAlertsOfTheDay($user);
            }
        }
    }
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LogService;
use App\Twig\SourceCodeExtension;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines the method that 'listens' to the 'kernel.controller' event, which is
 * triggered whenever a controller is executed in the application.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
readonly class ControllerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LogService          $logService,
        private Security            $security,
        private SourceCodeExtension $twigExtension,
        private UserRepository      $userRepository
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'registerCurrentController',
        ];
    }

    public function registerCurrentController(ControllerEvent $event): void
    {
        // this check is needed because in Symfony a request can perform any
        // number of sub-requests. See
        // https://symfony.com/doc/current/components/http_kernel.html#sub-requests
        if ($event->isMainRequest()) {
            /** @var User $user */
            $user = $this->security->getUser();

            $controller = $event->getController();
            $this->twigExtension->setController($controller);

            $this->logService->log($event->getRequest(), $user);

            if ($user && $controller[1] !== 'chatUpdate') {
                try {
                    $user->setLastActivityAt(new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')));
                } catch (Exception) {
                    $user->setLastActivityAt(new DateTimeImmutable());
                }
                // If the user is logged in from two browsers and logs out from one of them,
                // the last logout date field is no longer null. You have to set the logout date to null.
                if ($user->getLastLogout()) {
                    $user->setLastLogout(null);
                }
                $this->userRepository->save($user, true);
            }
        }
    }
}

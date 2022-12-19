<?php

namespace App\EventListener;

use App\Service\LogService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator,
                                private readonly Security              $security,
                                private readonly LogService            $logService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $this->logService->log($request, $this->security->getUser());

        $response = new RedirectResponse(
            $this->urlGenerator->generate('app_home'),
            Response::HTTP_SEE_OTHER
        );
        $event->setResponse($response);
    }
}
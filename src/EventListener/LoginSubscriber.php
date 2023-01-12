<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\LogService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

//use Symfony\Component\HttpFoundation\RedirectResponse;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

//use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Translation\LocaleSwitcher;

readonly class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(/*private readonly UrlGeneratorInterface $urlGenerator,*/
        private Security       $security,
        private LogService     $logService,
        private LocaleSwitcher $localeSwitcher
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLogin'];
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $request = $event->getRequest();
        $this->logService->log($request, $user);
        dump($request, $event);
        $preferredLanguage = $user->getPreferredLanguage();

        if ($preferredLanguage) {
            $this->localeSwitcher->setLocale($preferredLanguage);
        }

//        $response = new RedirectResponse(
//            $this->urlGenerator->generate('app_home'),
//            Response::HTTP_SEE_OTHER
//        );
//        $event->setResponse($response);
    }
}
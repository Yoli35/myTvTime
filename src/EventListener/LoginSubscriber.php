<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LogService;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
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
        private LocaleSwitcher $localeSwitcher,
        private UserRepository $userRepository
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
        try {
            $user->setLastLogin(new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')));
        } catch (Exception) {
            $user->setLastLogin(new DateTimeImmutable());
        }
        $user->setLastLogout(null);
        $this->userRepository->save($user, true);

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
<?php

namespace App\EventListener;

use App\Service\LogService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class MailSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SentMessageEvent::class => 'onSentMessage',
            FailedMessageEvent::class => 'onFailedMessage',
        ];
    }

    public function onSentMessage(SentMessageEvent $event): void
    {
        $message = $event->getMessage();
        dump($message);
//        dump($message->getOriginalMessage());
//        dump($message->getDebug());
    }

    public function onFailedMessage(FailedMessageEvent $event): void
    {
//        dump($event->getError());
    }
}
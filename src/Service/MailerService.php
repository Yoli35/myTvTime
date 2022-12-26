<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Throwable;

class MailerService
{
    public function __construct(private MailerInterface $mailer)
    {

    }

    public function sendEmail($subject = '', $content = '', $text = ''): void
    {
        $email = (new Email())
            ->from('noreply@mytvtime.fr')
            ->to('contact@mytvtime.fr')
            ->subject($subject)
            ->text($text)
            ->html($content);
        try {
            $this->mailer->send($email);
        } catch (Throwable $exception) {
            dump($exception);
        }
    }
}
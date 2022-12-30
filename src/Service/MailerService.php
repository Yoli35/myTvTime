<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailerService
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function sendEmail($subject = '', $contact = [], $imagePath = '', $image = ''): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@mytvtime.fr', 'No reply at myTvTime'))
            ->to(new Address('contact@mytvtime.fr', 'Contact at myTvTime'))
            ->subject($subject)
            ->htmlTemplate('emails/_contact.html.twig')
            ->context([
                'contact' => $contact,
                'image' => $image,
            ])
        ;
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
//            dump($e);
        }
    }
}
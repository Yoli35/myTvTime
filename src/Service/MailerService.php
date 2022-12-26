<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

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
//        if (strlen($imagePath)) {
//            /** @var UploadedFile $file */
//            $file = $contact['image'];
//            $email
//                ->addPart(new DataPart(new File($imagePath)))
//                ->addPart((new DataPart(new File($imagePath), 'image', $file->getClientMimeType()))->asInline());
//        }
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            dump($e);
        }
    }
}
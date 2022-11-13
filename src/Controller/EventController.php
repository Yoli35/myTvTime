<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/event', requirements: ['_locale' => 'fr|en|de|es'])]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $events = $eventRepository->findBy(['user' => $user], ['date' => 'DESC']);

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }
}

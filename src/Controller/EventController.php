<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\FileUploader;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/event', requirements: ['_locale' => 'fr|en|de|es'])]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $events = $eventRepository->findBy(['user' => $user], ['date' => 'DESC']);

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET','POST'])]
    public function new(Request $request, EventRepository $eventRepository, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $event = new Event($user);
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setVisible(true);

            /** @var UploadedFile $thumbnailFile */
            $thumbnailFile = $form->get('dropThumbnail')->getData();
            if ($thumbnailFile) {
                $thumbnailFileName = $fileUploader->upload($thumbnailFile, 'event_thumbnail');
                $fileToBeRemoved = $event->getThumbnail();
                if ($fileToBeRemoved) {
                    $fileUploader->removeFile($fileToBeRemoved, 'event_thumbnail');
                }
                $event->setThumbnail($thumbnailFileName);
            }
            /** @var UploadedFile $bannerFile */
            $bannerFile = $form->get('dropBanner')->getData();
            if ($bannerFile) {
                $bannerFileName = $fileUploader->upload($bannerFile, 'event_banner');
                $fileToBeRemoved = $event->getBanner();
                if ($fileToBeRemoved) {
                    $fileUploader->removeFile($fileToBeRemoved, 'event_banner');
                }
                $event->setBanner($bannerFileName);
            }

            $eventRepository->add($event, true);

            return $this->redirectToRoute('app_event');
        }
        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
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

<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\SerieViewing;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\AlertRepository;
use App\Repository\EventRepository;
use App\Repository\SerieViewingRepository;
use App\Service\DateService;
use App\Service\FileUploader;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/event', requirements: ['_locale' => 'fr|en|de|es'])]
class EventController extends AbstractController
{
    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly DateService $dateService,
        private readonly EventRepository $eventRepository,
        private readonly FileUploader $fileUploader,
        private readonly SerieViewingRepository $serieViewingRepository,
        private readonly TranslatorInterface $translator,
        private readonly ValidatorInterface $validator,
    )
    {
    }

    #[Route('/', name: 'app_event', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();

        $events = $this->eventRepository->findBy(['user' => $user, 'visible' => true], ['date' => 'DESC']);
        $events = array_map(function ($event) {
            return $event->toArray();
        }, $events);

        $alerts = $this->alertRepository->findBy(['user' => $user, 'activated' => true], ['date' => 'DESC']);
        $alerts = array_map(function ($alert) use ($locale) {
            $serieViewing = $this->serieViewingRepository->find($alert->getSerieViewingId());
            return [
                'id' => $serieViewing->getSerie()->getId(),
                'type' => 'alert',

                'banner' => $serieViewing->getSerie()->getBackdropPath(),
                'createdAt' => $alert->getCreatedAt(),
                'date' => $alert->getDate(),
                'description' => $alert->getMessage(),
                'images' => [],
                'name' => $serieViewing->getSerie()->getName(),
                'subheading' => $this->translator->trans('Original Title') . (in_array($locale, ['de', 'es', 'fr'])? ' ':'') . ': ' .$serieViewing->getSerie()->getOriginalName(),
                'thumbnail' => $serieViewing->getSerie()->getPosterPath(),
                'updatedAt' => null,
                'user' => $alert->getUser(),
                'visible' => true,
            ];
        }, $alerts);
        $events = array_merge($events, $alerts);
        // Tri sur la date
        usort($events, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        $countdownValues = array_map(function ($event) {
            return [
                'id' => $event['id'],
                "interval" => -1,
                'date' => $event['date']->format('Y-m-d H:i')
            ];
        }, $events);

        dump($events);

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'countdownValues' => $countdownValues,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $event = new Event($this->dateService);
        $event->setUser($this->getUser());
        $event->setName($this->translator->trans('New event'));
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $event);
            return $this->redirectToRoute('app_event');
        }
        // https://symfony.com/doc/current/validation.html
        $errors = $this->validator->validate($event);

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'errors' => $errors
        ]);
    }

    #[Route('/edit/{id}', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $event);
            return $this->redirectToRoute('app_event');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    function handleForm($form, Event $event): void
    {
        $event->setVisible(true);
        $event->setUpdatedAt(new DateTime());

        /** @var UploadedFile $thumbnailFile */
        $thumbnailFile = $form->get('dropThumbnail')->getData();
        if ($thumbnailFile) {
            $thumbnailFileName = $this->fileUploader->upload($thumbnailFile, 'event_thumbnail');
            $fileToBeRemoved = $event->getThumbnail();
            if ($fileToBeRemoved) {
                $this->fileUploader->removeFile($fileToBeRemoved, 'event_thumbnail');
            }
            $event->setThumbnail($thumbnailFileName);
        }
        /** @var UploadedFile $bannerFile */
        $bannerFile = $form->get('dropBanner')->getData();
        if ($bannerFile) {
            $bannerFileName = $this->fileUploader->upload($bannerFile, 'event_banner');
            $fileToBeRemoved = $event->getBanner();
            if ($fileToBeRemoved) {
                $this->fileUploader->removeFile($fileToBeRemoved, 'event_banner');
            }
            $event->setBanner($bannerFileName);
        }

        $this->eventRepository->add($event, true);
    }

    #[Route('/delete/{id}', name: 'app_event_delete', methods: ['GET'])]
    public function delete(Event $event): JsonResponse
    {
        if ($event->getThumbnail()) {
            $this->fileUploader->removeFile($event->getThumbnail(), 'event_thumbnail');
        }
        if ($event->getBanner()) {
            $this->fileUploader->removeFile($event->getBanner(), 'event_banner');
        }
        // TODO : Supprimer les images associées
        $this->eventRepository->remove($event, true);

        return $this->json(['status' => 200]);
    }

    #[Route('/show/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        $description = $event->getDescription();
        $description = preg_replace(
            ['/(https:\/\/\S+)/', '/\n/'],
            ['<a href="$1" target="_blank">$1</a>', '<br>'],
            $description);

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'description' => $description,
            'countdownValues' => [[
                'id' => $event->getId(),
                "interval" => -1,
                'date' => $event->getDate()->format('Y-m-d H:i')
            ]],
        ]);
    }
}

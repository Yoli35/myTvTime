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
use App\Service\ImageConfiguration;
use DateInterval;
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
        private readonly AlertRepository        $alertRepository,
        private readonly DateService            $dateService,
        private readonly EventRepository        $eventRepository,
        private readonly FileUploader           $fileUploader,
        private readonly ImageConfiguration     $imageConfiguration,
        private readonly SerieController        $serieController,
        private readonly SerieViewingRepository $serieViewingRepository,
        private readonly TranslatorInterface    $translator,
        private readonly ValidatorInterface     $validator,
    )
    {
    }

    #[Route('/', name: 'app_event', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $events = $this->eventRepository->findBy(['user' => $user, 'visible' => true], ['date' => 'DESC']);
        $now = $this->dateService->getNow($user->getTimezone());
        $events = array_map(function ($event) use ($now) {
            $diff = date_diff($now, $event->getDate());
            $e = $event->toArray();
            $e['past'] = $diff->invert;
            return $e;
        }, $events);

        $imgConfig = $this->imageConfiguration->getConfig();
        $watchProviderList = $this->serieController->getRegionProvider($imgConfig, 3, '', '');
        $alerts = $this->alertRepository->findBy(['user' => $user], ['date' => 'DESC']);
        $alerts = array_map(function ($alert) use ($watchProviderList, $now) {
            $serieViewing = $this->serieViewingRepository->find($alert->getSerieViewingId());

            $airDate = $this->dateService->newDateImmutable($alert->getDate()->format("Y-m-d H:i:s"), 'Europe/Paris');
            if ($serieViewing->isTimeShifted()) {
                $airDate = $airDate->add(new DateInterval('P1D'));
            }

            $diff = date_diff($now, $airDate);

            $provider = $alert->getProviderId() ? $watchProviderList[$alert->getProviderId()] : null;
            if ($provider) {
                $message = $alert->getMessage() . ' ' . $this->translator->trans('on') . ' ' . $provider['provider_name'] . '.';
            } else {
                $message = $alert->getMessage() . '.';
            }

            return [
                'id' => $serieViewing->getSerie()->getId(),
                'type' => 'alert',
                'past' => $diff->invert,

                'banner' => $serieViewing->getSerie()->getBackdropPath(),
                'createdAt' => $alert->getCreatedAt(),
                'date' => $airDate,
                'description' => '',
                'images' => [],
                'name' => $serieViewing->getSerie()->getName(),
                'subheading' => $message,
                'thumbnail' => $serieViewing->getSerie()->getPosterPath(),
                'updatedAt' => null,
                'user' => $alert->getUser(),
                'visible' => true,
                'watchProvider' => $provider,
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

//        dump($events);

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'countdownValues' => $countdownValues,
            'watchProviderList' => $watchProviderList,
            'user' => $user,
            'from' => 'my_events'
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

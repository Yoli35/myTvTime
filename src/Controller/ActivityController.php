<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityDay;
use App\Entity\User;
use App\Form\ActivityType;
use App\Repository\ActivityDayRepository;
use App\Repository\ActivityRepository;
use App\Service\LogService;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity')]
class ActivityController extends AbstractController
{
    public function __construct(private readonly LogService            $logService,
                                private readonly ActivityRepository    $activityRepository,
                                private readonly ActivityDayRepository $activityDayRepository)
    {

    }

    #[Route('/', name: 'app_activity_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $activity = $user->getActivity();

        if ($activity) {
            $days = $activity->getActivityDays();
            if (!$days->count() || !$this->todayActivityExist($days)) {
                $today = new ActivityDay($activity);
                $this->activityDayRepository->save($today, true);
                $activity->addActivityDay($today);
            }
        }

        $days = array_reverse($activity->getActivityDays()->toArray());

//        usort($days, function (ActivityDay $a, ActivityDay $b) {
//            return $b->getDay() <=> $a->getDay();
//        });

        return $this->render('activity/index.html.twig', [
            'activity' => $activity,
            'days' => $days,
        ]);
    }

    public function todayActivityExist($days): bool
    {
        try {
            $today = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        } catch (Exception) {
            $today = new DateTimeImmutable();
        }
        $today = $today->setTime(0, 0);

        foreach ($days as $day) {
            if ($day->getDay()->format('Y-m-d') === $today->format('Y-m-d')) {
                return true;
            }
        }
        return false;
    }

    #[Route('/new', name: 'app_activity_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $activity = new Activity($user);
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->activityRepository->save($activity, true);

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_activity_show', methods: ['GET'])]
    public function show(Activity $activity): Response
    {
        return $this->render('activity/show.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        dump($id);
        $activity = $this->activityRepository->find($id);

        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->activityRepository->save($activity, true);

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/edit.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_activity_delete', methods: ['POST'])]
    public function delete(Request $request, Activity $activity): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activity->getId(), $request->request->get('_token'))) {
            $this->activityRepository->remove($activity, true);
        }

        return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
    }
}

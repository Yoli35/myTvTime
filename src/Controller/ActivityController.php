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

//      Ajout du numéro de semaine
//      --------------------------
//        foreach ($days as $day) {
//            $day->setWeek($day->getDay()->format('W'));
//            $this->activityDayRepository->save($day, false);
//        }
//        $this->activityDayRepository->flush();

        $currentWeek = $days[0]->getWeek();
        $currentYear = $days[0]->getDay()->format('Y');
        $weeks = [];

        foreach ($days as $day) {
            $week = $day->getWeek();
            $year = $day->getDay()->format('Y');
            $weeks[$currentYear - $year][$currentWeek - $week][] = $day;
        }
        dump($weeks);

        return $this->render('activity/index.html.twig', [
            'activity' => $activity,
            'days' => $days,
            'years' => $weeks,
            'currentWeek' => $currentWeek,
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

    #[Route('/{id}/edit', name: 'app_activity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
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

    // /activity/3/stand-up/7/13/1
    #[Route('/{id}/stand-up', name: 'app_activity_stand_up_toggle', methods: ['GET'])]
    public function standUpToggle(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        /** @var Activity $activity */
        $activity = $this->activityRepository->find($id);

        $day = $request->query->getInt('day');
        $up = $request->query->getInt('up');
        $newValue = $request->query->getInt('val');

        $activityDay = $this->activityDayRepository->find($day);
        $ups = $activityDay->getStandUp();
        $ups[$up] = $newValue;
        $standUpResult = array_sum($ups);
        $standUpGoal = $activity->getStandUpGoal();
        $activityDay->setStandUp($ups);
        $activityDay->setStandUpResult($standUpResult);
        $activityDay->setStandUpRingCompleted($standUpResult >= $standUpGoal);
        $this->activityDayRepository->save($activityDay, true);

        $hours = $this->render('blocks/activity/_standUp.html.twig', [
            'ups' => $ups,
        ]);

        return $this->json([
            'success' => true,
            'html' => $hours,
            'result' => $standUpResult,
            'percent' => floor($standUpResult / $standUpGoal * 100),
            'goal' => $activityDay->isStandUpRingCompleted(),
        ]);
    }

    #[Route('/{id}/save/data', name: 'app_activity_save_data', methods: ['GET'])]
    public function saveDataActivity(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        /** @var Activity $activity */
        $activity = $this->activityRepository->find($id);

        $day = $request->query->getInt('day');
        $name = $request->query->getAlpha('name');
        $value = $request->query->get('value');

        $goalCompleted = false;
        $blockName = "";

        $activityDay = $this->activityDayRepository->find($day);
        $activityDay->{'set' . ucfirst($name)}($value);
        $this->activityDayRepository->save($activityDay, true);

        // si le nom (name) contient "Result" alors on compare la valeur à la valeur de l'objectif
        if (str_contains($name, 'Result')) {
            $blockName = str_replace('Result', '', $name);
            $getter = 'get' . ucfirst(str_replace('Result', 'Goal', $name));
            $setter = 'set' . ucfirst(str_replace('Result', 'RingCompleted', $name));

            $goal = $activity->{$getter}();
            $goalCompleted = ($value >= $goal);
            $activityDay->{$setter}($goalCompleted);
            $this->activityDayRepository->save($activityDay, true);
            $percent = round(($value / $goal) * 100);
        } else {
            $blockName = $name;
            $percent = 0;
        }

        return $this->json([
            'success' => true,
            'goal' => $goalCompleted,
            'blockSelector' => '.' . $blockName,
            'percent' => $percent,
            'circleSelector' => $blockName,
        ]);
    }
}

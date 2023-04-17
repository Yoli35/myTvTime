<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityDay;
use App\Entity\ActivityExerciseGoal;
use App\Entity\ActivityMoveGoal;
use App\Entity\ActivityStandUpGoal;
use App\Entity\User;
use App\Form\ActivityType;
use App\Repository\ActivityDayRepository;
use App\Repository\ActivityExerciseGoalRepository;
use App\Repository\ActivityMoveGoalRepository;
use App\Repository\ActivityRepository;
use App\Repository\ActivityStandUpGoalRepository;
use App\Service\LogService;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity')]
class ActivityController extends AbstractController
{
    public function __construct(private readonly LogService                     $logService,
                                private readonly LoggerInterface                $loggerInterface,
                                private readonly ActivityRepository             $activityRepository,
                                private readonly ActivityDayRepository          $activityDayRepository,
                                private readonly ActivityMoveGoalRepository     $activityMoveGoalRepository,
                                private readonly ActivityExerciseGoalRepository $activityExerciseGoalRepository,
                                private readonly ActivityStandUpGoalRepository  $activityStandUpGoalRepository)
    {

    }

    #[Route('/', name: 'app_activity_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $activity = $user->getActivity();
//        dump($activity);

        if ($activity) {
            $days = $activity->getActivityDays();
            if (!$days->count() || !$this->todayActivityExist($days)) {
                $today = new ActivityDay($activity);
                $activity->addActivityDay($today);
                $this->activityDayRepository->save($today, true);
            }
        }

        $days = array_reverse($activity->getActivityDays()->toArray());

//        usort($days, function (ActivityDay $a, ActivityDay $b) {
//            return $b->getDay() <=> $a->getDay();
//        });

        $currentWeek = $days[0]->getWeek();
        $currentYear = $days[0]->getDay()->format('Y');
        $years = [];

        foreach ($days as $day) {
            $week = $day->getWeek();
            $year = $day->getDay()->format('Y');
            $years[$currentYear - $year][$currentWeek - $week][] = $day;
        }

        $yearIndex = count($years) - 1;
        $weekIndex = count($years[$yearIndex]) - 1;
        if ($weekIndex) {
            $firstWeekDayCount = count($years[$yearIndex][$weekIndex]);

            if ($firstWeekDayCount < 7) {
                $years[$yearIndex][$weekIndex] = array_merge($years[$yearIndex][$weekIndex], array_fill(0, 7 - $firstWeekDayCount, null));
            }
        }

        $goals = [];
        $goals['move'] = $activity->getMoveGoals()->toArray();
        $goals['move'][count($goals['move']) - 1]->setEnd($this->now());
        $goals['exercise'] = $activity->getExerciseGoals()->toArray();
        $goals['exercise'][count($goals['exercise']) - 1]->setEnd($this->now());
        $goals['standUp'] = $activity->getStandUpGoals()->toArray();
        $goals['standUp'][count($goals['standUp']) - 1]->setEnd($this->now());
//        dump($goals);

        return $this->render('activity/index.html.twig', [
            'activity' => $activity,
            'goals' => $goals,
            'days' => $days,
            'years' => $years,
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
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $activity = new Activity($user);
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->activityRepository->save($activity);
            $start = $this->now();
            $start = $start->setTime(0, 0);
            $moveGoal = new ActivityMoveGoal($activity, $activity->getMoveGoal(), $start);
            $this->activityMoveGoalRepository->save($moveGoal);
            $activity->addMoveGoal($moveGoal);
            $exerciseGoal = new ActivityExerciseGoal($activity, $activity->getExerciseGoal(), $start);
            $this->activityExerciseGoalRepository->save($exerciseGoal);
            $activity->addExerciseGoal($exerciseGoal);
            $standUpGoal = new ActivityStandUpGoal($activity, $activity->getStandUpGoal(), $start);
            $this->activityStandUpGoalRepository->save($standUpGoal);
            $activity->addStandUpGoal($standUpGoal);
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

        $moveGoals = $activity->getMoveGoals();
        $count = count($moveGoals);
        /** @var ActivityMoveGoal $lastMoveGoal */
        $lastMoveGoal = $count ? $moveGoals[$count - 1] : null;

        $exerciseGoals = $activity->getExerciseGoals();
        $count = count($exerciseGoals);
        /** @var ActivityExerciseGoal $lastExerciseGoal */
        $lastExerciseGoal = $count ? $exerciseGoals[$count - 1] : null;

        $standUpGoals = $activity->getStandUpGoals();
        $count = count($standUpGoals);
        /** @var ActivityStandUpGoal $lastStandUpGoal */
        $lastStandUpGoal = $count ? $standUpGoals[$count - 1] : null;

        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->activityRepository->save($activity, true);

            $newMoveGoal = $activity->getMoveGoal();
            $newExerciseGoal = $activity->getExerciseGoal();
            $newStandUpGoal = $activity->getStandUpGoal();

            $now = $this->now();
            $yesterday = $now->sub(new DateInterval('P1D'));

            if ($lastMoveGoal === null || $lastMoveGoal->getAmount() !== $newMoveGoal) {
                if ($lastMoveGoal && $lastMoveGoal->getStart()->format("Y-m-d") === $now->format("Y-m-d")) {
                    $lastMoveGoal->setAmount($newMoveGoal);
                    $this->activityMoveGoalRepository->save($lastMoveGoal);
                } else {
                    if ($lastMoveGoal) {
                        $lastMoveGoal->setEnd($yesterday);
                        $this->activityMoveGoalRepository->save($lastMoveGoal);
                    }
                    $moveGoal = new ActivityMoveGoal($activity, $activity->getMoveGoal(), $now);
                    $this->activityMoveGoalRepository->save($moveGoal);
                    $activity->addMoveGoal($moveGoal);
                }
            }
            if ($lastExerciseGoal === null || $lastExerciseGoal->getAmount() !== $newExerciseGoal) {
                if ($lastExerciseGoal && $lastExerciseGoal->getStart()->format("Y-m-d") === $now->format("Y-m-d")) {
                    $lastExerciseGoal->setAmount($newExerciseGoal);
                    $this->activityExerciseGoalRepository->save($lastExerciseGoal);
                } else {
                    if ($lastExerciseGoal) {
                        $lastExerciseGoal->setEnd($yesterday);
                        $this->activityExerciseGoalRepository->save($lastExerciseGoal);
                    }
                    $exerciseGoal = new ActivityExerciseGoal($activity, $activity->getExerciseGoal(), $now);
                    $this->activityExerciseGoalRepository->save($exerciseGoal);
                    $activity->addExerciseGoal($exerciseGoal);
                }
            }
            if ($lastStandUpGoal === null || $lastStandUpGoal->getAmount() !== $newStandUpGoal) {
                if ($lastStandUpGoal && $lastStandUpGoal->getStart()->format("Y-m-d") === $now->format("Y-m-d")) {
                    $lastStandUpGoal->setAmount($newStandUpGoal);
                    $this->activityStandUpGoalRepository->save($lastStandUpGoal);
                } else {
                    if ($lastStandUpGoal) {
                        $lastStandUpGoal->setEnd($yesterday);
                        $this->activityStandUpGoalRepository->save($lastStandUpGoal);
                    }
                    $standUpGoal = new ActivityStandUpGoal($activity, $activity->getStandUpGoal(), $now);
                    $this->activityStandUpGoalRepository->save($standUpGoal);
                    $activity->addStandUpGoal($standUpGoal);
                }
            }
            $this->activityRepository->save($activity, true);

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/edit.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

    public function now(): ?DateTimeImmutable
    {
        try {
            $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
            return $now->setTime(0, 0);
        } catch (Exception $e) {
            $this->loggerInterface->error('New date with "Europe/Paris" time zone : ' . $e->getFile() . ' (line ' . $e->getLine() . ') : ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return new DateTimeImmutable();
        }
    }

    #[Route('/{id}/stand-up', name: 'app_activity_stand_up_toggle', methods: ['GET'])]
    public function standUpToggle(Request $request, int $id): Response
    {
//        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());

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
//        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());

        /** @var Activity $activity */
        $activity = $this->activityRepository->find($id);

        $day = $request->query->getInt('day');
        $name = $request->query->getAlpha('name');
        $value = $request->query->get('value');

        $goalCompleted = false;

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

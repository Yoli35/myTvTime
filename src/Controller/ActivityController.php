<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityChallenge;
use App\Entity\ActivityDay;
use App\Entity\ActivityExerciseGoal;
use App\Entity\ActivityMoveGoal;
use App\Entity\ActivityStandUpGoal;
use App\Entity\User;
use App\Form\ActivityType;
use App\Repository\ActivityChallengeRepository;
use App\Repository\ActivityDayRepository;
use App\Repository\ActivityExerciseGoalRepository;
use App\Repository\ActivityMoveGoalRepository;
use App\Repository\ActivityRepository;
use App\Repository\ActivityStandUpGoalRepository;
use App\Service\DateService;
use DateInterval;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/activity', name: 'app_activity_', requirements: ['_locale' => 'fr|en|de|es'], options: ['utf8' => true])]
class ActivityController extends AbstractController
{
    public function __construct(
        private readonly ActivityChallengeRepository    $activityChallengeRepository,
        private readonly ActivityDayRepository          $activityDayRepository,
        private readonly ActivityExerciseGoalRepository $activityExerciseGoalRepository,
        private readonly ActivityMoveGoalRepository     $activityMoveGoalRepository,
        private readonly ActivityRepository             $activityRepository,
        private readonly ActivityStandUpGoalRepository  $activityStandUpGoalRepository,
        private readonly DateService                    $dateService,
        private readonly TranslatorInterface            $translator,
    )
    {

    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $now = $this->dateService->getNow("UTC", true);
        $dayOfTheWeek = $now->format('N');

        $activity = $user->getActivity();

        if (!$activity) {
            return $this->render('activity/index.html.twig', [
                'activity' => $activity,
            ]);
        }
        $challenges = $this->getChallenges($activity, $now);

        $days = $this->activityDayRepository->getActivityDays($activity->getId());

        if (!count($days)) {
            $today = new ActivityDay($activity, $now);
            $activity->addActivityDay($today);
            $this->activityDayRepository->save($today, true);
            $days[0] = $today;
        }

        // S'il manque des jours, on les ajoute et on recharge le tableau
        $results = $this->checkForMissingDays($activity, $days, $now);
        $missingDayCount = $results['missing days count'];
        $missingDayIsToday = $results['missing day is today'];

        if ($missingDayCount) {
            $days = $this->activityDayRepository->getActivityDays($activity->getId(), 0, intval($dayOfTheWeek) + 10 * 7);
            if ($missingDayIsToday) {
                $this->addFlash("success", $this->translator->trans("Hello, today is added to your activity"));
            } else {
                $this->addFlash("success", $missingDayCount > 1 ? $this->translator->trans("count missing days added", ['count' => $missingDayCount]) : $this->translator->trans("One missing day added"));
            }
        }

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
        $goals['move'][count($goals['move']) - 1]->setEnd($now);
        $goals['exercise'] = $activity->getExerciseGoals()->toArray();
        $goals['exercise'][count($goals['exercise']) - 1]->setEnd($now);
        $goals['standUp'] = $activity->getStandUpGoals()->toArray();
        $goals['standUp'][count($goals['standUp']) - 1]->setEnd($now);

        $breadcrumb = [
            ['name' => $this->translator->trans('Home'), 'url' => $this->generateUrl('app_home')],
            ['name' => $this->translator->trans('Activity'), 'url' => $this->generateUrl('app_activity_index')],
        ];

//        dump(['days' => $days]);

        return $this->render('activity/index.html.twig', [
            'activity' => $activity,
            'challenges' => $challenges,
            'goals' => $goals,
            'days' => $days,
            'years' => $years,
            'currentWeek' => $currentWeek,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function checkForMissingDays(Activity $activity, array $days, $now): array
    {
        $whileDay = $activity->getCreatedAt();
        $whileDayFormat = $whileDay->format('Y-m-d');
        $nowFormat = $now->format('Y-m-d');
        $missingDays = 0;
        $missingDayIsToday = false;

        while ($whileDayFormat <= $nowFormat) {

            if ($this->missingDay($whileDayFormat, $days)) {
//                dump(['missing day' => $whileDayFormat, 'missing days (datetime)' => $whileDay]);
                $day = new ActivityDay($activity, $whileDay);
                $activity->addActivityDay($day);
                $this->activityDayRepository->save($day, true);
                $missingDays++;
                if ($whileDayFormat === $nowFormat) {
                    $missingDayIsToday = true;
                }
            }

            $whileDay = $whileDay->add(new DateInterval('P1D'));
            $whileDayFormat = $whileDay->format('Y-m-d');
        }
        return ['missing days count' => $missingDays, 'missing day is today' => $missingDayIsToday];
    }

    public function missingDay($dateFormat, $days): bool
    {
        foreach ($days as $day) {
            if ($day->getDay()->format('Y-m-d') === $dateFormat) {
                return false;
            }
        }
        return true;
    }

    public function getChallenges($activity, $now): array
    {
        $challenges = array_map(function ($challenge) use ($activity) {
            /** @var ActivityChallenge $challenge */
            if ($challenge->inProgress()) {
                $discipline = $challenge->getChallenge();
                $value = $challenge->getValue();
                $goal = $challenge->getGoal();
                $startAt = $challenge->getStartAt();
                $endAt = $challenge->getEndAt();
                $month = 0;

                $startMonth = $startAt->format('m');
                $endMonth = $endAt->format('m');
                if ($startMonth === $endMonth) {
                    $startDay = $startAt->format('d');
                    $endAtDay = $endAt->format('d');
                    $theoreticalEndAtDay = $startAt->format('t');
                    if ($startDay == '1' && $endAtDay == $theoreticalEndAtDay) {
                        $month = $startMonth;
                    }
                }
                $daysChallengeMet = $this->activityDayRepository->checkChallenge($activity->getId(), $discipline, $value, $month, $startAt->format('Y-m-d'), $endAt->format('Y-m-d'));
                $challenge->setProgress(count($daysChallengeMet));
                $challenge->setCompleted($challenge->getProgress() >= $goal);
                $this->activityChallengeRepository->save($challenge, true);
//                dump([
//                        'discipline' => $discipline,
//                        'value' => $value,
//                        'goal' => $goal,
//                        'startAt' => $startAt->format('Y-m-d'),
//                        'endAt' => $endAt->format('Y-m-d'),
//                        'month' => $month,
//                        'Days when the challenge is completed' => $daysChallengeMet]
//                );
            }
            return $challenge;
        }, $activity->getActivityChallenges()->toArray());
//        dump($challenges);

        return [
            'inProgress' => array_filter($challenges, function ($challenge) {
                return $challenge->inProgress();
            }),
            'completed' => array_filter($challenges, function ($challenge) {
                return !$challenge->inProgress();
            }),
        ];
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $activity = new Activity($user);
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->activityRepository->save($activity);
            $start = $this->dateService->getNow("UTC", true);
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

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
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

            $now = $this->dateService->getNow("UTC", true);
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

    #[Route('/{id}/stand-up', name: 'stand_up_toggle', methods: ['GET'])]
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

    #[Route('/{id}/save/data', name: 'save_data', methods: ['GET'])]
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

        // si le nom (name) contient "Result" alors, on compare la valeur à la valeur de l'objectif
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

    #[Route('/{id}/save/day', name: 'save_day', methods: ['GET'])]
    public function saveDayActivity(Request $request, int $id): Response
    {
        $values = json_decode($request->query->get('values'), true);

        $activity = $this->activityRepository->find($id);
        $activityDay = $this->activityDayRepository->find($values['dayId']);

        $moveResult = $values['moveResult'];
        $exerciseResult = $values['exerciseResult'];
        $standUpResult = $values['standUpResult'];
        $distance = str_replace(",", ".", $values['distance']);
        $steps = $values['steps'];

        $activityDay->setMoveResult($moveResult);
        $activityDay->setExerciseResult($exerciseResult);
        $activityDay->setSteps($steps);
        $activityDay->setDistance(floatval($distance));

        $moveGoal = $activity->getMoveGoal();
        $exerciseGoal = $activity->getExerciseGoal();
        $standUpGoal = $activity->getStandUpGoal();

        if ($moveResult >= $moveGoal) {
            $activityDay->setMoveRingCompleted(true);
        }
        if ($exerciseResult >= $exerciseGoal) {
            $activityDay->setExerciseRingCompleted(true);
        }
        if ($standUpResult >= $standUpGoal) {
            $activityDay->setStandUpRingCompleted(true);
        }
        $this->activityDayRepository->save($activityDay, true);

        $dayBlock = $this->render('blocks/activity/_day.html.twig', [
            'activity' => $activity,
            'day' => $activityDay,
        ]);

        return $this->json([
            'success' => true,
            'dayBlock' => $dayBlock->getContent(),
            'moveProgress' => round($moveResult / $moveGoal * 100),
            'exerciseProgress' => round($exerciseResult / $exerciseGoal * 100),
            'standUpProgress' => round($standUpResult / $standUpGoal * 100),
        ]);
    }
}

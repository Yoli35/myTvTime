<?php

namespace App\Controller;

use App\Entity\ActivityChallenge;
use App\Entity\User;
use App\Form\ActivityChallengeType;
use App\Repository\ActivityChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/activity/challenge', name: 'app_activity_challenge_', requirements: ['_locale' => 'fr|en|de|es'])]
class ActivityChallengeController extends AbstractController
{
    public function __construct(
        private readonly ActivityChallengeRepository $activityChallengeRepository,
        private readonly EntityManagerInterface      $entityManager,
        private readonly TranslatorInterface         $translator,
    )
    {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $breadcrumb = [
            ['name' => $this->translator->trans('Activity'), 'url' => $this->generateUrl('app_activity_index')],
        ];
        $challenges = $this->activityChallengeRepository->findAll();
        dump($challenges);
        return $this->render('activity_challenge/index.html.twig', [
            'challenges' => $challenges,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $from = $request->query->get('from');
        $breadcrumb = [
            ['name' => $this->translator->trans('Activity'), 'url' => $this->generateUrl('app_activity_index')],
            ['name' => $this->translator->trans('Challenge list'), 'url' => $this->generateUrl('app_activity_challenge_index')],
        ];
        if ($from == 'show') {
            $id = $request->query->get('id');
            $challenge = $this->activityChallengeRepository->find($id);
            $breadcrumb[] = ['name' => $this->translator->trans('Challenge') . " “ " . $challenge->getName() . " ”", 'url' => $this->generateUrl('app_activity_challenge_show', ['id' => $id])];
        }
        $breadcrumb[] = ['name' => $this->translator->trans('New'), 'color' => 'var(--gradient-orange-40-alpha-50)'];
        /** @var User $user */
        $user = $this->getUser();

        $challenge = new ActivityChallenge($user->getActivity());
        $form = $this->createForm(ActivityChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($challenge);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_activity_challenge_' . $request->query->get('from', 'index'), [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity_challenge/new.html.twig', [
            'challenge' => $challenge,
            'breadcrumb' => $breadcrumb,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(ActivityChallenge $challenge): Response
    {
        $breadcrumb = [
            ['name' => $this->translator->trans('Activity'), 'url' => $this->generateUrl('app_activity_index')],
            ['name' => $this->translator->trans('Challenge list'), 'url' => $this->generateUrl('app_activity_challenge_index')],
        ];
        return $this->render('activity_challenge/show.html.twig', [
            'challenge' => $challenge,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActivityChallenge $challenge): Response
    {
        $breadcrumb = [
            ['name' => $this->translator->trans('Activity'), 'url' => $this->generateUrl('app_activity_index')],
            ['name' => $this->translator->trans('Challenge list'), 'url' => $this->generateUrl('app_activity_challenge_index')],
            ['name' => $this->translator->trans('Challenge') . " “ " . $challenge->getName() . " ”", 'url' => $this->generateUrl('app_activity_challenge_show', ['id' => $challenge->getId()])],
            ['name' => $this->translator->trans('Edit')]
        ];

        $form = $this->createForm(ActivityChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_activity_challenge_' . $request->query->get('from', 'index'), [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity_challenge/edit.html.twig', [
            'challenge' => $challenge,
            'breadcrumb' => $breadcrumb,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, ActivityChallenge $activityChallenge): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activityChallenge->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($activityChallenge);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_activity_challenge_index', [], Response::HTTP_SEE_OTHER);
    }
}

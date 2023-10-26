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
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ActivityChallengeRepository $activityChallengeRepository): Response
    {
        return $this->render('activity_challenge/index.html.twig', [
            'activity_challenges' => $activityChallengeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $activityChallenge = new ActivityChallenge($user->getActivity());
        $form = $this->createForm(ActivityChallengeType::class, $activityChallenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activityChallenge);
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity_challenge/new.html.twig', [
            'activity_challenge' => $activityChallenge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(ActivityChallenge $challenge): Response
    {
        $breadcrumb = [
            ['name' => $this->translator->trans('Activity'), 'url' => $this->generateUrl('app_activity_index')],
            ['name' => $this->translator->trans('Challenge'), 'url' => $this->generateUrl('app_activity_challenge_index')],
        ];
        return $this->render('activity_challenge/show.html.twig', [
            'challenge' => $challenge,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActivityChallenge $activityChallenge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActivityChallengeType::class, $activityChallenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity_challenge/edit.html.twig', [
            'activity_challenge' => $activityChallenge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, ActivityChallenge $activityChallenge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activityChallenge->getId(), $request->request->get('_token'))) {
            $entityManager->remove($activityChallenge);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activity_challenge_index', [], Response::HTTP_SEE_OTHER);
    }
}

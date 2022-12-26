<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ContactType;
use App\Service\LogService;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactController extends AbstractController
{

    public function __construct(private readonly LogService          $logService,
                                private readonly MailerService       $mailerService,
                                private readonly TranslatorInterface $translator)
    {
    }

    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();
        $from = $request->query->get('from');
        $params = json_decode($request->query->get('params'), true);

        $form = $this->createForm(ContactType::class, ['username' => $user?->getUsername(), 'email' => $user->getEmail()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactFormData = $form->getData();
            $subject = $this->translator->trans('Contact request from') . ' ' . $contactFormData['email'];
            $content = $contactFormData['username'] . ' ' . $this->translator->trans('sent you the following message') . ': ' . $contactFormData['message'];
            $this->mailerService->sendEmail(subject: $subject, content: $content);
            $this->addFlash('success', $this->translator->trans('Your message has been successfully send.'));
            $this->addFlash('warning', $this->translator->trans('It may take a few days to receive a response.'));

            if ($from) {
                return $this->redirectToRoute('app_' . $from, $params);
            }
            return $this->redirectToRoute('app_home');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

//use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/{_locale}/login', name: 'app_login', requirements: ['locale' => 'fr|en|de|es'], methods: ['GET', 'POST'])]
    public function index(Request $request/*, AuthenticationUtils $authenticationUtils*/): Response
    {
        $user = new User();
        $form = $this->createForm(LoginFormType::class, $user);
        $form->handleRequest($request);
        // get the login error if there is one
        // $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
//        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'loginForm' => $form->createView(),
//            'last_username' => $lastUsername,
//            'error'         => $error,
        ]);
    }
}

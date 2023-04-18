<?php

namespace App\Controller;

//use App\chat\ChatMessageType;
//use App\Entity\ChatDiscussion;
//use App\Repository\ChatDiscussionRepository;
//use App\Repository\ChatMessageRepository;
//use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Bundle\SecurityBundle\Security;
//use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    public function __construct(
//        private readonly UserRepository           $userRepository,
//        private readonly ChatDiscussionRepository $chatDiscussionRepository,
//        private readonly ChatMessageRepository    $chatMessageRepository,
//        private readonly Security                 $security
    )
    {
    }

    #[Route(path: '/chat/update', name: 'app_chat_update', methods: ['GET'])]
    public function chatUpdate(): Response
    {
        return $this->render('blocks/chat/_chat.html.twig');
    }

//    #[Route(path: '/chat/{recipientId}', name: 'app_chat')]
//    public function chatDiscussion(Request $request, int $recipientId): Response
//    {
//        $user = $this->security->getUser();
//        $recipient = $this->userRepository->find($recipientId);
//        $chatDiscussion = $this->chatDiscussionRepository->findOneBy(['user' => $user, 'recipient' => $recipient]);
//        if (!$chatDiscussion) {
//            $chatDiscussion = new ChatDiscussion($user, $recipient);
//            $this->chatDiscussionRepository->save($chatDiscussion, true);
//        }
//        $chatMessages = $this->chatMessageRepository->findBy(['chatDiscussion' => $chatDiscussion], ['created_at' => 'ASC']);
//        $form = $this->createForm(ChatMessageType::class);
//        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
//            $chatMessage = $form->getData();
//            $chatMessage->setChatDiscussion($chatDiscussion);
//            $this->chatMessageRepository->save($chatMessage);
//            $this->addFlash('success', 'Votre message a bien été envoyé');
//            return $this->redirectToRoute('app_chat', ['recipientId' => $recipientId]);
//        }
//        return $this->render('chat/chat.html.twig', [
//            'chatMessages' => $chatMessages,
//            'form' => $form->createView(),
//            'recipient' => $recipient,
//        ]);
//    }
}
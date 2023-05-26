<?php

namespace App\Controller;

use App\Entity\ChatDiscussion;
use App\Repository\ChatDiscussionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    public function __construct(
        private readonly UserRepository           $userRepository,
        private readonly ChatDiscussionRepository $chatDiscussionRepository,
    )
    {
    }

    #[Route(path: '/chat/update', name: 'app_chat_update', methods: ['GET'])]
    public function chatUpdate(): Response
    {
        return $this->render('blocks/chat/_chat.html.twig');
    }

    #[Route(path: '/chat/discussion/open/{recipientId}', name: 'app_chat_open')]
    public function chatDiscussionOpen(int $recipientId): Response
    {
        $user = $this->getUser();

        $recipient = $this->userRepository->find($recipientId);
        $chatDiscussion = $this->chatDiscussionRepository->findOneBy(['user' => $user, 'recipient' => $recipient]);
        if (!$chatDiscussion) {
            $chatDiscussion = new ChatDiscussion($user, $recipient);
        } else {
            $chatDiscussion->setOpen(true);
        }
        $this->chatDiscussionRepository->save($chatDiscussion, true);

        return $this->render('blocks/chat/_conversation.html.twig', [
            'discussion' => $chatDiscussion,
            'user'       => $user,
        ]);
    }

    #[Route(path: '/chat/discussion/close/{discussionId}', name: 'app_chat_close')]
    public function chatDiscussionClose(int $discussionId): Response
    {
        $discussion = $this->chatDiscussionRepository->find($discussionId);
        $discussion->setOpen(false);
        $this->chatDiscussionRepository->save($discussion, true);

        return $this->json(['success' => true]);
    }
}
<?php

namespace App\Controller;

use App\Entity\ChatDiscussion;
use App\Entity\ChatMessage;
use App\Entity\User;
use App\Repository\ChatDiscussionRepository;
use App\Repository\ChatMessageRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    public function __construct(
        private readonly UserRepository           $userRepository,
        private readonly ChatDiscussionRepository $chatDiscussionRepository,
        private readonly ChatMessageRepository    $chatMessageRepository,
    )
    {
    }

    #[Route(path: '/chat/update', name: 'app_chat_update', methods: ['GET'])]
    public function chatUpdate(): Response
    {
        return $this->render('blocks/chat/_userList.html.twig');
    }

    #[Route(path: '/chat/discussion/open/{recipientId}', name: 'app_chat_open')]
    public function chatDiscussionOpen(int $recipientId): Response
    {
        $user = $this->getUser();

        $recipient = $this->userRepository->find($recipientId);
        $chatDiscussion = null;
        $discussionOwner = false;
        $myDiscussion = $this->chatDiscussionRepository->findOneBy(['user' => $user, 'recipient' => $recipient]);
        $buddyDiscussion = $this->chatDiscussionRepository->findOneBy(['user' => $recipient, 'recipient' => $user]);
        if ($myDiscussion) {
            $chatDiscussion = $myDiscussion;
            $discussionOwner = true;
        } elseif ($buddyDiscussion) {
            $chatDiscussion = $buddyDiscussion;
        }
        if (!$chatDiscussion) {
            $chatDiscussion = new ChatDiscussion($user, $recipient);
        } else {
            if ($discussionOwner) {
                $chatDiscussion->setOpenUser(true);
            } else {
                $chatDiscussion->setOpenRecipient(true);
            }
        }
        $this->chatDiscussionRepository->save($chatDiscussion, true);

//        return $this->render('blocks/chat/_discussion.html.twig', [
//            'discussion' => $chatDiscussion,
//            'user'       => $user,
//        ]);
        return $this->render('blocks/chat/_chat.html.twig');
    }

    #[Route(path: '/chat/discussion/close/{discussionId}', name: 'app_chat_close')]
    public function chatDiscussionClose(int $discussionId): Response
    {
        /* @var User $user */
        $user = $this->getUser();
        $discussion = $this->chatDiscussionRepository->find($discussionId);
        if ($discussion->getUser()->getId() == $user->getId())
            $discussion->setOpenUser(false);
        else
            $discussion->setOpenRecipient(false);
        $this->chatDiscussionRepository->save($discussion, true);

        return $this->json(['success' => true]);
    }

    #[Route(path: '/chat/discussion/typing/{discussionId}', name: 'app_chat_typing', methods: ['POST'])]
    public function chatDiscussionTyping(Request $request, int $discussionId): Response
    {
        $typing = json_decode($request->getContent(), true)['typing'];
        $user = $this->getUser();
        $discussion = $this->chatDiscussionRepository->find($discussionId);
        if (!$discussion) {
            return $this->json(['success' => false]);
        }
        if ($discussion->getUser() === $user) {
            $discussion->setTypingUser($typing);
        } else {
            $discussion->setTypingRecipient($typing);
        }
        $this->chatDiscussionRepository->save($discussion, true);

        return $this->json(['success' => true]);
    }

    #[Route(path: '/chat/discussion/message/{discussionId}', name: 'app_chat_discussion', methods: ['POST'])]
    public function chatDiscussion(Request $request, int $discussionId): Response
    {
        $message = json_decode($request->getContent(), true)['message'];
        $discussion = $this->chatDiscussionRepository->find($discussionId);
        /** @var User $user */
        $user = $this->getUser();

        $chatMessage = new ChatMessage($discussion, $user, $message);
        $this->chatMessageRepository->save($chatMessage, true);
        $discussion->addChatMessage($chatMessage);
        $discussion->setTypingUser(false);
        $discussion->setTypingRecipient(false);
        $discussion->setLastMessageAt($this->newDate('now', 'Europe/Paris'));
        $this->chatDiscussionRepository->save($discussion, true);

        if ($discussion->getUser() !== $user && $discussion->getRecipient() !== $user) {
            throw $this->createNotFoundException();
        }

        return $this->render('blocks/chat/_discussion.html.twig', [
            'discussion' => $discussion,
            'user'       => $user,
            'activate'   => true,
        ]);
    }

    public function newDate($dateString, $timeZone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime($dateString, new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay)   $date->setTime(0, 0);

        return $date;
    }
}
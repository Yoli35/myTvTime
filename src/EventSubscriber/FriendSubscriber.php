<?php

namespace App\EventSubscriber;

use App\Controller\UserController;
use App\Entity\User;
use App\Repository\FriendRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\User\UserInterface;

class FriendSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UserController $userController)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(ControllerEvent $event)
    {
//        $authorizedControllers = [
//            'app_home', 'app_blog',
//            'app_movie_list', 'app_movies_search', 'app_movies_by_genre', 'app_movies_by_date',
//            'app_serie_top_rated', 'app_serie_airing_today', 'app_serie_on_the_air', 'app_serie_search',
//            'app_personal_profile', 'app_event', 'app_event_new', 'app_event_show',
//            'app_personal_movies', 'app_movie', 'app_movie_collection', 'app_collection', 'app_collection_show',
//            'app_people',
//            'app_serie_index', 'app_serie_show', 'app_serie_tmdb_season',
//            'app_youtube', 'app_youtube_video', 'app_youtube_search',
//            'app_tik_tok', 'app_tik_tok_video',
//            'app_contact', 'app_rgpd',
//            ];
//        $names = [];
//        $attributes = $event->getAttributes();
//        foreach ($attributes as $key => $values) {
//            foreach ($values as $value) {
//                if ($value->getName()) {
//                    $names[] = $value->getName();
//                }
//            }
//        }
//        $name = $names[0];
//        dump($attributes, strlen($name));
//        if (in_array($names[0], $authorizedControllers)) {
            $this->userController->checkPendingFriendRequest();
//        }
    }
}
<?php
//
namespace App\Components;
//
use App\Repository\MovieRepository;
//use Doctrine\Persistence\ObjectRepository;
//use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
//use Symfony\UX\LiveComponent\Attribute\LiveProp;
//use Symfony\UX\LiveComponent\DefaultActionTrait;
//
//#[AsLiveComponent('user_movie_search')]
readonly class UserMovieSearchComponent
{
//    use DefaultActionTrait;
//
//    #[LiveProp(writable: true)]
//    public string $query = '';
//    #[LiveProp]
//    public int $id;
//    #[LiveProp]
//    public string $poster_url;
//
//    private ObjectRepository $repoUM;
//
    public function __construct(private MovieRepository $repoUM)
    {
//        $this->repoUM = $repoUM;
    }
//
//    public function mount($id, $poster_url, $poster_size): void
//    {
//        $this->id = $id;
//        $this->poster_url = $poster_url . $poster_size;
//    }
//
    public function movie_results($userId, $query): array
    {
        $results = [];
        if (strlen(/*$this->query*/$query)) {
            $results = $this->repoUM->searchUserMovies(/*$this->id, $this->query*/$userId, $query);
        }
        return $results;
    }
}
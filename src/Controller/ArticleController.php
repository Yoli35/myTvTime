<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\AnswerRepository;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Service\LogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    public function __construct(private readonly LogService $logService)
    {
    }

    #[Route('/{_locale}/blog', name: 'app_blog', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        $this->logService->log($request, $this->getUser());
        $articles = $articleRepository->findByPublishedAtDesc();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/{_locale}/blog/article/{id}', name: 'app_blog_article', requirements: ['_locale' => 'fr|en|de|es'])]
    public function article(Request $request, $id, ArticleRepository $articleRepository, CommentRepository $commentRepository): Response
    {
        $this->logService->log($request, $this->getUser());
        $article = $articleRepository->find($id);

        $content = preg_replace(
            [
                '#\{"path": "(.*?)", "class": "(.*?)", "style": "(.*?)"\}#',
                '#\{"path": "(.*?)", "class": "(.*?)"\}#',
                '#\{"path": "(.*?)", "style": "(.*?)"\}#'
            ],
            [
                '<img src="/images/articles/images/$1" class="$2" style="$3" alt="$1">',
                '<img src="/images/articles/images/$1" class="$2" alt="$1">',
                '<img src="/images/articles/images/$1" style="$2" alt="$1">'
            ],
            $article->getContent());

        /** @var User $user */
        $user = $this->getUser();

        if ($user) {
            $comment = new Comment();
            $comment->setArticle($article);
            $comment->setUser($user);
            $comment->setText("");
            $form = $this->createForm(CommentType::class, $comment);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setUpdatedAt(new \DateTimeImmutable());
                $commentRepository->add($comment, true);

                $comment = new Comment();
                $comment->setArticle($article);
                $comment->setUser($user);
                $comment->setText("");
                $form = $this->createForm(CommentType::class, $comment);
            }
        } else {
            $form = null;
        }

        $comments = $commentRepository->findBy(['article' => $article], ['createdAt' => 'DESC']);

        return $this->render('article/article.html.twig', [
            'article' => $article,
            'content' => $content,
            'form' => $user ? $form->createView() : null,
            'comments' => $comments,
        ]);
    }

    #[Route('/{_locale}/blog/article/answer/{cid}', name: 'app_blog_article_add_answer', requirements: ['_locale' => 'fr|en|de|es'])]
    public function addAnswer(Request $request, $cid, CommentRepository $commentRepository, AnswerRepository $answerRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $comment = $commentRepository->find($cid);
        $text = $request->query->get('text');

        $answer = new Answer();
        $answer->setUser($user);
        $answer->setComment($comment);
        $answer->setCreatedAt(new \DateTimeImmutable());
        $answer->setUpdatedAt(new \DateTimeImmutable());
        $answer->setText($text);
        $answerRepository->add($answer, true);

        return $this->render('blocks/article/_reaction.html.twig', [
            'reaction' => $answer,
        ]);

//        return $this->json([
//            "comment_id" => $cid,
//            "text" => $text,
//            "created_at" => $answer->getCreatedAt(),
//            "updated_at" => $answer->getUpdatedAt(),
//        ]);
    }
}

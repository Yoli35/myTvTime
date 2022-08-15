<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route('/{_locale}/blog', name: 'app_blog', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findByPublishedAtDesc();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/{_locale}/blog/article/{id}', name: 'app_blog_article', requirements: ['_locale' => 'fr|en|de|es'])]
    public function article(Request $request, $id, ArticleRepository $articleRepository, CommentRepository $commentRepository): Response
    {
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
        }
        else {
            $form = null;
        }

        $comments = $commentRepository->findBy(['article' => $article], ['createdAt' => 'DESC']);

        return $this->render('article/article.html.twig', [
            'article' => $article,
            'content' => $content,
            'form' => $user?$form->createView():null,
            'comments' => $comments,
        ]);
    }
}
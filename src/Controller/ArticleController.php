<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function article($id, ArticleRepository $articleRepository): Response
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

        return $this->render('article/article.html.twig', [
            'article' => $article,
            'content' => $content
        ]);
    }
}

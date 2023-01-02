<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Repository\AnswerRepository;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Service\FileUploader;
use App\Service\LogService;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        /** @var User $user */
        $user = $this->getUser();

        $this->logService->log($request, $this->getUser());
        $articles = $articleRepository->findByPublishedAtDesc();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'userArticleIds' => $this->getUserArticleIds($user, $articleRepository),
        ]);
    }

    #[Route('/{_locale}/blog/article/{id}', name: 'app_blog_article', requirements: ['_locale' => 'fr|en|de|es'])]
    public function article(Request $request, $id, ArticleRepository $articleRepository, CommentRepository $commentRepository): Response
    {
        $this->logService->log($request, $this->getUser());
        $article = $articleRepository->find($id);

        $content = preg_replace(
            [
                '#{"path": "(.*?)", "class": "(.*?)", "style": "(.*?)"}#',
                '#{"path": "(.*?)", "class": "(.*?)"}#',
                '#{"path": "(.*?)", "style": "(.*?)"}#'
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
                $comment->setCreatedAt(new DateTimeImmutable());
                $comment->setUpdatedAt(new DateTimeImmutable());
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
            'userArticleIds' => $this->getUserArticleIds($user, $articleRepository),
        ]);
    }

    public function getUserArticleIds($user, $repository)
    {
        $userArticleIds = [];
        if ($user) {
            $userArticles = $repository->findBy(['user' => $user]);
            $userArticleIds = array_map(function ($article) {
                return $article->getId();
            }, $userArticles);
        }
        return $userArticleIds;
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
        $answer->setCreatedAt(new DateTimeImmutable());
        $answer->setUpdatedAt(new DateTimeImmutable());
        $answer->setText($text);
        $answerRepository->add($answer, true);

        return $this->render('blocks/article/_reaction.html.twig', [
            'reaction' => $answer,
        ]);
    }

    #[Route('/{_locale}/blog/new', name: 'app_blog_new', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET', 'POST'])]
    public function new(Request $request, ArticleRepository $repository, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $article = new Article($user);
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $article->setPublishedAt(new DateTimeImmutable());
            $this->handleForm($form, $article, $fileUploader, $repository);
            return $this->redirectToRoute('app_blog');
        }

        return $this->render('article/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{_locale}/blog/edit/{id}', name: 'app_blog_edit', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, ArticleRepository $repository, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $article, $fileUploader, $repository);
            return $this->redirectToRoute('app_blog');
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    function handleForm($form, Article $article, FileUploader $fileUploader, ArticleRepository $repository): void
    {
        /** @var UploadedFile $thumbnailFile */
        $thumbnailFile = $form->get('dropThumbnail')->getData();
        /** @var UploadedFile $bannerFile */
        $bannerFile = $form->get('dropBanner')->getData();

        if ($thumbnailFile) {
            $thumbnailFileName = $fileUploader->upload($thumbnailFile, 'article_thumbnail');
            $fileToBeRemoved = $article->getThumbnail();
            if ($fileToBeRemoved) {
                $fileUploader->removeFile($fileToBeRemoved, 'article_thumbnail');
            }
            $article->setThumbnail($thumbnailFileName);
        }
        if ($bannerFile) {
            $bannerFileName = $fileUploader->upload($bannerFile, 'article_banner');
            $fileToBeRemoved = $article->getBanner();
            if ($fileToBeRemoved) {
                $fileUploader->removeFile($fileToBeRemoved, 'article_banner');
            }
            $article->setBanner($bannerFileName);
        }
//        $article->setIsPublished(true);
        $article->setUpdatedAt(new DateTime());
        $repository->add($article, true);
    }

    #[Route('/{_locale}/blog/delete/{id}', name: 'app_article_delete', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET'])]
    public function delete(Article $article, ArticleRepository $repository, FileUploader $fileUploader): JsonResponse
    {
        if ($article->getThumbnail()) {
            $fileUploader->removeFile($article->getThumbnail(), 'article_thumbnail');
        }
        if ($article->getBanner()) {
            $fileUploader->removeFile($article->getBanner(), 'article_banner');
        }
        // TODO : Supprimer les images associées
        $repository->remove($article, true);

        return $this->json(['status' => 200]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Article;
use App\Entity\ArticleImage;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Repository\AnswerRepository;
use App\Repository\ArticleImageRepository;
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
    public function __construct(private readonly LogService             $logService,
                                private readonly ArticleRepository      $articleRepository,
                                private readonly ArticleImageRepository $imageRepository,
                                private readonly FileUploader           $fileUploader)
    {
    }

    #[Route('/{_locale}/blog', name: 'app_blog', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

//        $this->logService->log($request, $this->getUser());
        $articles = $this->articleRepository->findByPublishedAtDesc();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'userArticleIds' => $this->getUserArticleIds($user, $this->articleRepository),
        ]);
    }

    #[Route('/{_locale}/blog/article/{id}', name: 'app_blog_article', requirements: ['_locale' => 'fr|en|de|es'])]
    public function article(Request $request, $id, CommentRepository $commentRepository): Response
    {
//        $this->logService->log($request, $this->getUser());
        $article = $this->articleRepository->find($id);

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
            'userArticleIds' => $this->getUserArticleIds($user, $this->articleRepository),
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
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $article = new Article();
        $article->setUser($user);
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $article->setPublishedAt(new DateTimeImmutable());
            $this->handleForm($form, $article);
            return $this->redirectToRoute('app_blog_article', ['id' => $article->getId()]);
        }

        return $this->render('article/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{_locale}/blog/edit/{id}', name: 'app_blog_edit', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $article);
            return $this->redirectToRoute('app_blog_article', ['id' => $article->getId()]);
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    function handleForm($form, Article $article): void
    {
        /** @var UploadedFile $thumbnailFile */
        $thumbnailFile = $form->get('dropThumbnail')->getData();
        /** @var UploadedFile $bannerFile */
        $bannerFile = $form->get('dropBanner')->getData();
        /** @var UploadedFile[] $images */
        $images = [$form->get('image1')->getData(),
            $form->get('image2')->getData(),
            $form->get('image3')->getData(),
            $form->get('image4')->getData()
        ];

        if ($thumbnailFile) {
            $thumbnailFileName = $this->fileUploader->upload($thumbnailFile, 'article_thumbnail');
            $fileToBeRemoved = $article->getThumbnail();
            if ($fileToBeRemoved) {
                $this->fileUploader->removeFile($fileToBeRemoved, 'article_thumbnail');
            }
            $article->setThumbnail($thumbnailFileName);
        }
        if ($bannerFile) {
            $bannerFileName = $this->fileUploader->upload($bannerFile, 'article_banner');
            $fileToBeRemoved = $article->getBanner();
            if ($fileToBeRemoved) {
                $this->fileUploader->removeFile($fileToBeRemoved, 'article_banner');
            }
            $article->setBanner($bannerFileName);
        }
//        $article->setIsPublished(true);
        $article->setUpdatedAt(new DateTime());
        $this->articleRepository->add($article, true);

        for ($i=0;$i<4;$i++) {
            if ($images[$i]) {
                $imageFileName = $this->fileUploader->upload($images[$i], 'article_images');
                $articleImage = new ArticleImage();
                $articleImage->setPath($imageFileName);
                $articleImage->setArticle($article);
                $this->imageRepository->add($articleImage, true);
            }
        }
    }

    #[Route('/{_locale}/blog/delete/{id}', name: 'app_article_delete', requirements: ['_locale' => 'fr|en|de|es'], methods: ['GET'])]
    public function delete(Article $article): JsonResponse
    {
        if ($article->getThumbnail()) {
            $this->fileUploader->removeFile($article->getThumbnail(), 'article_thumbnail');
        }
        if ($article->getBanner()) {
            $this->fileUploader->removeFile($article->getBanner(), 'article_banner');
        }
        // TODO : Supprimer les images associées
        $this->articleRepository->remove($article, true);

        return $this->json(['status' => 200]);
    }
}

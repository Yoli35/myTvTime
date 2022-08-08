<?php

namespace App\Controller;

use App\Entity\ArticleImage;
use App\Form\ArticleImageType;
use App\Repository\ArticleImageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/article/image')]
class ArticleImageController extends AbstractController
{
    #[Route('/', name: 'app_article_image_index', methods: ['GET'])]
    public function index(ArticleImageRepository $articleImageRepository): Response
    {
        return $this->render('article_image/index.html.twig', [
            'article_images' => $articleImageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_article_image_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ArticleImageRepository $articleImageRepository): Response
    {
        $articleImage = new ArticleImage();
        $form = $this->createForm(ArticleImageType::class, $articleImage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $articleImageRepository->add($articleImage, true);

            return $this->redirectToRoute('app_article_image_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article_image/new.html.twig', [
            'article_image' => $articleImage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_image_show', methods: ['GET'])]
    public function show(ArticleImage $articleImage): Response
    {
        return $this->render('article_image/show.html.twig', [
            'article_image' => $articleImage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_image_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ArticleImage $articleImage, ArticleImageRepository $articleImageRepository): Response
    {
        $form = $this->createForm(ArticleImageType::class, $articleImage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $articleImageRepository->add($articleImage, true);

            return $this->redirectToRoute('app_article_image_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article_image/edit.html.twig', [
            'article_image' => $articleImage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_image_delete', methods: ['POST'])]
    public function delete(Request $request, ArticleImage $articleImage, ArticleImageRepository $articleImageRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$articleImage->getId(), $request->request->get('_token'))) {
            $articleImageRepository->remove($articleImage, true);
        }

        return $this->redirectToRoute('app_article_image_index', [], Response::HTTP_SEE_OTHER);
    }
}

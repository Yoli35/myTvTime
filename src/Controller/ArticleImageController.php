<?php

namespace App\Controller;

use App\Entity\ArticleImage;
use App\Form\ArticleImageType;
use App\Repository\ArticleImageRepository;
use App\Service\FileUploader;
use App\Service\LogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/article/image')]
class ArticleImageController extends AbstractController
{
    public function __construct(private readonly LogService $logService)
    {
    }

    #[Route('/article/images', name: 'app_article_image_index', methods: ['GET'])]
    public function index(Request $request, ArticleImageRepository $articleImageRepository): Response
    {
//        $this->logService->log($request, $this->getUser());

        return $this->render('article_image/index.html.twig', [
            'article_images' => $articleImageRepository->findAll(),
        ]);
    }

    #[Route('/article/images/new', name: 'app_article_image_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ArticleImageRepository $articleImageRepository, FileUploader $fileUploader): Response
    {
//        $this->logService->log($request, $this->getUser());
        $articleImage = new ArticleImage();
        $form = $this->createForm(ArticleImageType::class, $articleImage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $image = $form->get('drop')->getData();
            if ($image) {
                $imageName = $fileUploader->upload($image, 'article_images');
                $articleImage->setPath($imageName);
                $articleImageRepository->add($articleImage, true);
            }

            return $this->redirectToRoute('app_article_image_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article_image/new.html.twig', [
            'article_image' => $articleImage,
            'form' => $form,
        ]);
    }

    #[Route('/article/images/edit/{id}', name: 'app_article_image_edit', methods: ['GET', 'POST'])]
    public function edit($id, Request $request, ArticleImage $articleImage, ArticleImageRepository $articleImageRepository): Response
    {
//        $this->logService->log($request, $this->getUser());
        $form = $this->createForm(ArticleImageType::class, $articleImage);
        $form->handleRequest($request);

        $absolutePath = $this->getParameter('article_s_images_directory');

        if ($form->isSubmitted() && $form->isValid()) {

            $prevEntityState = $articleImageRepository->findOneById($id);
            if (strcmp($prevEntityState['path'], $articleImage->getPath())) {
                rename($absolutePath . '/' . $prevEntityState['path'], $absolutePath . '/' . $articleImage->getPath());
            }

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
        if ($this->isCsrfTokenValid('delete' . $articleImage->getId(), $request->request->get('_token'))) {

            $absolutePath = $this->getParameter('article_s_images_directory');
            unlink($absolutePath . '/' . $articleImage->getPath());

            $articleImageRepository->remove($articleImage, true);
        }

        return $this->redirectToRoute('app_article_image_index', [], Response::HTTP_SEE_OTHER);
    }
}

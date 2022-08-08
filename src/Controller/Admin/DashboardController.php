<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\ArticleImage;
use App\Entity\Genre;
use App\Entity\ImageConfig;
use App\Entity\TikTokVideo;
use App\Entity\User;
use App\Entity\UserMovie;
use App\Entity\YoutubeVideo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
//use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class DashboardController extends AbstractDashboardController
{
//    private string $locale;
//
//    public function __construct(Request $request)
//    {
//        $this->locale = $request->getLocale();
//    }
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/admin')]
    public function index(): Response
    {
         $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
         return $this->redirect($adminUrlGenerator->setController(ArticleCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('MyTvTime');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Users');
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
        yield MenuItem::section('Blog');
        yield MenuItem::linkToCrud('articles', 'fa fa-movie', Article::class);
        yield MenuItem::linkToCrud('Images', 'fa fa-image', ArticleImage::class);
        yield MenuItem::section('Movies');
        yield MenuItem::linkToCrud('Movies', 'fa fa-movie', UserMovie::class);
        yield MenuItem::section('Youtube Videos');
        yield MenuItem::linkToCrud('Youtube', 'fa fa-youtube', YoutubeVideo::class);
        yield MenuItem::section('Tik Tok Videos');
        yield MenuItem::linkToCrud('Tik Tok', 'fa fa-movie', TikTokVideo::class);
        yield MenuItem::section('Entities');
        yield MenuItem::subMenu('Settings', 'fa fa-sliders')->setSubItems([
            MenuItem::linkToCrud('Image Configuration', 'fa fa-image', ImageConfig::class),
            MenuItem::linkToCrud('Genres', 'fa fa-file-text', Genre::class),
        ]);
        yield MenuItem::section('Other');
        yield MenuItem::linkToUrl('Homepage', 'fa fa-rocket', '/');
//        yield MenuItem::linkToUrl('Blog', 'fa fa-rocket', '/'.$this->locale.'/blog');
        yield MenuItem::linkToLogout('Logout', 'fa fa-rocket');
    }
}

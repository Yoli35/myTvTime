<?php

namespace App\Controller\Admin;

use App\Entity\Genre;
use App\Entity\ImageConfig;
use App\Entity\User;
use App\Entity\UserMovie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class DashboardController extends AbstractDashboardController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/admin')]
    public function index(): Response
    {
//        return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
         $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
         return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
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
        yield MenuItem::section('Movies');
        yield MenuItem::linkToCrud('Movies', 'fa fa-movie', UserMovie::class);
        yield MenuItem::section('Entities');
        yield MenuItem::subMenu('Settings', 'fa fa-sliders')->setSubItems([
            MenuItem::linkToCrud('Image Configuration', 'fa fa-image', ImageConfig::class),
            MenuItem::linkToCrud('Genres', 'fa fa-file-text', Genre::class),
        ]);
        yield MenuItem::section('Other');
//        yield MenuItem::linkToRoute('Homepage', 'fa fa-rocket', 'app_home');
        yield MenuItem::linkToUrl('Homepage', 'fa fa-rocket', '/');
        yield MenuItem::linkToLogout('Logout', 'fa fa-rocket');
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['username' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('email'),
            BooleanField::new('isVerified'),
            TextField::new('username'),
            ImageField::new('avatar')
                ->setBasePath('images/users/avatars')
                ->setUploadDir('public/images/users/avatars')
                ->setUploadedFileNamePattern('[uuid].[extension]'),
            ImageField::new('banner')
                ->setBasePath('images/users/banners')
                ->setUploadDir('public/images/users/banners')
                ->setUploadedFileNamePattern('[uuid].[extension]'),
            ];
    }

}

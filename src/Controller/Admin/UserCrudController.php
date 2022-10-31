<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
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
            yield IdField::new('id')->hideOnForm();
            yield TextField::new('email');
            yield BooleanField::new('isVerified');
            yield TextField::new('username');
            yield TextField::new('city');
            yield TextField::new('zipCode');
            yield TextField::new('country');
            yield ImageField::new('avatar')
                ->setBasePath('images/users/avatars')
                ->setUploadDir('public/images/users/avatars')
                ->setUploadedFileNamePattern('[uuid].[extension]');
            yield ImageField::new('banner')
                ->setBasePath('images/users/banners')
                ->setUploadDir('public/images/users/banners')
                ->setUploadedFileNamePattern('[uuid].[extension]');
        yield AssociationField::new('movies');
        yield AssociationField::new('series');
        yield AssociationField::new('youtubeVideos');
        yield AssociationField::new('tiktoks');
        yield AssociationField::new('articles');
        yield AssociationField::new('events');
        yield AssociationField::new('friends');
    }

}

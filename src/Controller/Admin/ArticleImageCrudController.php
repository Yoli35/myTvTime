<?php

namespace App\Controller\Admin;

use App\Entity\ArticleImage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class ArticleImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ArticleImage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('article');
        yield ImageField::new('path')
            ->setBasePath('images/articles/images')
            ->setUploadDir('public/images/articles/images')
            ->setUploadedFileNamePattern('[slug].[extension]');
    }
}

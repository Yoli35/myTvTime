<?php

namespace App\Controller\Admin;

use App\Entity\YoutubeVideo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class YoutubeVideoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return YoutubeVideo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDateFormat('medium')
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            DateTimeField::new('publishedAt'),
            AssociationField::new('channel'),
            ImageField::new('thumbnailDefaultPath')
                ->setBasePath('images/youtube')
                ->setUploadDir('public/images/youtube')
                ->setUploadedFileNamePattern('[uuid].[extension]'),
        ];
    }
}

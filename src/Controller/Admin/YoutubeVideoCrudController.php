<?php

namespace App\Controller\Admin;

use App\Entity\YoutubeVideo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class YoutubeVideoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return YoutubeVideo::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            TextEditorField::new('description'),
            ImageField::new('thumbnailDefaultPath')
                ->setBasePath('images/youtube')
                ->setUploadDir('public/images/youtube')
                ->setUploadedFileNamePattern('[uuid].[extension]'),
        ];
    }
}

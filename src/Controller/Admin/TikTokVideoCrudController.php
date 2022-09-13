<?php

namespace App\Controller\Admin;

use App\Entity\TikTokVideo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TikTokVideoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TikTokVideo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDateFormat('medium')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ImageField::new('thumbnailUrl'),
            TextField::new('title'),
            DateTimeField::new('addedAt')
        ];
    }
}

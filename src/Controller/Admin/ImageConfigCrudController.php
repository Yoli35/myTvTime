<?php

namespace App\Controller\Admin;

use App\Entity\ImageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ImageConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ImageConfig::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}

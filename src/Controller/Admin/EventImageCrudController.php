<?php

namespace App\Controller\Admin;

use App\Entity\EventImage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EventImage::class;
    }

    public function configureFields(string $pageName): iterable
    {
       yield IdField::new('id')->hideOnForm();
       yield TextField::new('caption');
        yield AssociationField::new('event');
        yield ImageField::new('path')
            ->setBasePath('images/events/images')
            ->setUploadDir('public/images/events/images')
            ->setUploadedFileNamePattern('[slug].[extension]');
    }
}

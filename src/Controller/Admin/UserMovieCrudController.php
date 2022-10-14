<?php

namespace App\Controller\Admin;

use App\Entity\UserMovie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserMovieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserMovie::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title');
        yield TextField::new('original_title');
        yield TextField::new('release_date');
        yield NumberField::new('movie_db_id');
        yield NumberField::new('runtime');
        if ($pageName == Crud::PAGE_EDIT || $pageName == Crud::PAGE_NEW) {
            yield TextEditorField::new('overviewFr');
            yield TextEditorField::new('overviewEn');
            yield TextEditorField::new('overviewEs');
            yield TextEditorField::new('overviewDe');
        }
        yield AssociationField::new('movieCollections');
    }
}

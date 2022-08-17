<?php

namespace App\Controller\Admin;

use App\Entity\UserMovie;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserMovieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserMovie::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
//            IdField::new('id'),
            TextField::new('title'),
            TextField::new('original_title'),
            TextField::new('release_date'),
            NumberField::new('movie_db_id'),
            NumberField::new('runtime'),
            AssociationField::new('myMovieCollections'),
        ];
    }
}

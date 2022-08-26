<?php

namespace App\Controller\Admin;

use App\Entity\MovieCollection;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MovieCollectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MovieCollection::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('ma collection')
            ->setEntityLabelInPlural('mes collections')
            ->setDateFormat('medium')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user')->hideOnForm();
        yield TextField::new('title');
        if ($pageName == Crud::PAGE_EDIT || $pageName == Crud::PAGE_NEW) {
            yield TextEditorField::new('description');
        }
        yield ImageField::new('thumbnail')
            ->setBasePath('images/collections/thumbnails')
            ->setUploadDir('public/images/collections/thumbnails')
            ->setUploadedFileNamePattern('[slug].[extension]')
            ->setColumns(6);
        yield ImageField::new('banner')
            ->setBasePath('images/collections/banners')
            ->setUploadDir('public/images/collections/banners')
            ->setUploadedFileNamePattern('[slug].[extension]')
            ->setColumns(6);
        yield ColorField::new('color');
//        if ($pageName == Crud::PAGE_EDIT || $pageName == Crud::PAGE_NEW) {
            yield AssociationField::new('movies');
//        }
    }

    public function createEntity(string $entityFqcn): MovieCollection
    {
        $datetime = (new DateTimeImmutable());

        $collection = new MovieCollection;

        $collection->setUser($this->getUser());
        $collection->setCreatedAt($datetime);
        $collection->setUpdatedAt($datetime);

        return $collection;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var MovieCollection $entityInstance */
        $entityInstance->setUpdatedAt(new DateTimeImmutable());

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('article')
            ->setEntityLabelInPlural('articles')
            ->setDateFormat('medium')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield BooleanField::new('isPublished');
        yield TextField::new('title');
        yield TextField::new('abstract');
        if ($pageName == Crud::PAGE_INDEX) {
            yield DateField::new('publishedAt');
            yield DateField::new('createdAt');
            yield DateField::new('updatedAt');
        }
        if ($pageName == Crud::PAGE_EDIT || $pageName == Crud::PAGE_NEW) {

            yield TextEditorField::new('content');
            yield ImageField::new('thumbnail')
                ->setBasePath('images/articles/thumbnails')
                ->setUploadDir('public/images/articles/thumbnails')
                ->setUploadedFileNamePattern('[uuid].[extension]')
                ->setColumns(6);
            yield ImageField::new('banner')
                ->setBasePath('images/articles/banners')
                ->setUploadDir('public/images/articles/banners')
                ->setUploadedFileNamePattern('[uuid].[extension]')
                ->setColumns(6);
            yield AssociationField::new('articleImages');
        }
    }

    public function createEntity(string $entityFqcn): Article
    {
        $datetime = (new DateTimeImmutable());

        $article = new Article();

        $article->setUser($this->getUser());
        $article->setCreatedAt($datetime);
        $article->setUpdatedAt($datetime);
        $article->setPublishedAt($datetime);

        return $article;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Article $entityInstance */
        $entityInstance->setUpdatedAt(new DateTimeImmutable());

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}

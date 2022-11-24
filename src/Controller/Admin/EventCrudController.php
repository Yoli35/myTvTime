<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('event')
            ->setEntityLabelInPlural('events')
            ->setDateFormat('medium')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name');
        yield TextField::new('subheading');
        yield DateTimeField::new('date');
        yield BooleanField::new('visible');
        if ($pageName == Crud::PAGE_EDIT || $pageName == Crud::PAGE_NEW) {
            yield TextEditorField::new('description');
        }
            yield ImageField::new('thumbnail')
                ->setBasePath('images/events/thumbnails')
                ->setUploadDir('public/images/events/thumbnails')
                ->setUploadedFileNamePattern('[uuid].[extension]')
                ->setColumns(6);
            yield ImageField::new('banner')
                ->setBasePath('images/events/banners')
                ->setUploadDir('public/images/events/banners')
                ->setUploadedFileNamePattern('[uuid].[extension]')
                ->setColumns(6);
            yield AssociationField::new('images');
    }

    public function createEntity(string $entityFqcn): Event
    {
        $event = new Event();

        $event->setUser($this->getUser());

        return $event;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Event $entityInstance */
        $entityInstance->setUpdatedAt(new \DateTime());

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}

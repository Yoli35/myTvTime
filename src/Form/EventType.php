<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Dropzone\Form\DropzoneType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class'=> "w100"],
                'required' => true
            ])
            ->add('subheading', TextType::class, [
                'label' => 'Subheading',
                'attr' => ['class'=> "w100"],
                'required' => false
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class'=> "w100", 'rows' => 8],
                'required' => false
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Date',
                'attr' => ['class'=> "w100 d-flex-1"],
                'required' => true
            ])
            ->add('dropThumbnail', DropzoneType::class, [
                'label' => 'Profile Image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'w100',
                    'placeholder' => 'Click here or drop a thumbnail file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('dropBanner', DropzoneType::class, [
                'label' => 'Banner Image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'w100',
                    'placeholder' => 'Click here or drop a banner file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Add event',
                'attr' => ['class' => 'btn btn-secondary'],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}

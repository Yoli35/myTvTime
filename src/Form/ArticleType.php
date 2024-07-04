<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Dropzone\Form\DropzoneType;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publish this article',
                'label_attr' => ['class' => 'switcher-with-label'],
                'required' => false,
            ])
            ->add('abstract', TextType::class, [
                'label' => 'Abstract / Subtitle',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => ['class'=> "w100", 'rows' => 8],
                'required' => true
            ])
            ->add('dropThumbnail', DropzoneType::class, [
                'label' => 'Thumbnail (JPG, PNG file)',
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
                'label' => 'Banner (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'w100',
                    'placeholder' => 'Click here or drop a banner file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('image1', DropzoneType::class, [
                'label' => 'image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'hidden',
                    'placeholder' => 'Click here or drop an image file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('image2', DropzoneType::class, [
                'label' => 'image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'hidden',
                    'placeholder' => 'Click here or drop an image file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('image3', DropzoneType::class, [
                'label' => 'image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'hidden',
                    'placeholder' => 'Click here or drop an image file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('image4', DropzoneType::class, [
                'label' => 'image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'hidden',
                    'placeholder' => 'Click here or drop an image file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Add article',
                'attr' => ['class' => 'btn btn-secondary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}

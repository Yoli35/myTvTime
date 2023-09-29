<?php

namespace App\Form;

use App\Entity\MovieList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;

class MovieListType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => $this->translator->trans('Title'),
                'attr' => ['class', "w100"],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans('Description'),
                'attr' => ['class' => 'w100', 'rows' => 5],
                'required' => false,
            ])
            ->add('color', ColorType::class, [
                'label' => $this->translator->trans('Color'),
            ])
            ->add('dropThumbnail', DropzoneType::class, [
                'label' => $this->translator->trans('Thumbnail Image (JPG, PNG file)'),
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'w100',
                    'placeholder' => 'Click here or drop a thumbnail image file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('dropBanner', DropzoneType::class, [
                'label' => $this->translator->trans('Banner Image (JPG, PNG file)'),
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'w100',
                    'placeholder' => 'Click here or drop a banner image file',
                    'accept' => 'image/*'
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Add list',
                'attr' => ['class' => 'btn btn-secondary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MovieList::class,
        ]);
    }
}

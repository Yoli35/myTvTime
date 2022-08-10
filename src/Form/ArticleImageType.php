<?php


namespace App\Form;

use App\Entity\ArticleImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Dropzone\Form\DropzoneType;

class ArticleImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('path')
            ->add('article', null, [
                'required' => true,
            ])
            ->add('drop', DropzoneType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => ['data-controller' => 'mydropzone'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleImage::class,
        ]);
    }
}
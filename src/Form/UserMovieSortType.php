<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserMovieSortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sort', ChoiceType::class, [
                'choices' => [
                    'Add Date' => 'id',
                    'Title' => 'title',
                    'Release Date' => 'release_date',
                    'Runtime' => 'runtime',
                ],
                'label' => 'Sort by',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('order', ChoiceType::class, [
                'choices' => [
                    'Ascending' => 'ASC',
                    'Descending' => 'DESC',
                ],
                'label' => 'Order',
                'expanded' => false,
                'multiple' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null, // 'App\DTO\UserMovieSortDTO',
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}

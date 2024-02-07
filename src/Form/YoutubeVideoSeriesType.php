<?php

namespace App\Form;

use App\Entity\YoutubeVideoSeries;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class YoutubeVideoSeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        dump($options);
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => ['class' => 'w100'],
                'required' => true,
            ])
            ->add('serieId', ChoiceType::class, [
                'label' => 'Series',
                'choices' => $options['user_series'],
                'choice_translation_domain' => false,
                'attr' => ['class' => 'w100'],
                'required' => false,
            ])
            ->add('format', TextType::class, [
                'label' => 'Format',
                'attr' => ['class' => 'w100'],
                'required' => true,
            ])
            ->add('regex', CheckboxType::class, [
                'label' => 'Regex',
                'required' => false,
            ])
            ->add('matchesCollection', CollectionType::class, [
                'entry_type' => VideoSeriesMatchType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'label' => 'Matches',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => YoutubeVideoSeries::class,
            'user_series' => [],
        ]);
        $resolver->setAllowedTypes('user_series', 'array');
    }
}

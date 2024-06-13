<?php

namespace App\Form;

use App\Entity\Serie;
use App\Entity\SerieAlternateOverview;
use App\Entity\WatchProvider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OverviewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
//        dump($options);
        $builder
            ->add('type', ChoiceType::class, [
                'attr' => ['class' => 'w100'],
                'choices' => [
                    'Series overview' => 'series',
                    'Season overview' => 'season',
                ],
                'label' => 'Type',
                'required' => true,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'required' => true,
                'data' => $options['data']['content'],
            ])
            ->add('season_number')
            ->add('watch_provider', ChoiceType::class, [
                'attr' => ['class' => 'w100'],
                'choice_translation_domain' => false,
                'choices' => $options['watch_providers'],
                'label' => 'Watch Provider',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'watch_providers' => [],
        ]);
        $resolver->setAllowedTypes('watch_providers', 'array');
    }
}

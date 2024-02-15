<?php

namespace App\Form;

use App\Entity\SerieAlternateOverview;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeriesAlternateOverviewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        dump($options);
        $builder
            ->add('overview')
            ->add('locale', LanguageType::class, [
                'attr' => ['class' => 'w100'],
                'label' => 'Language',
                'required' => true,
            ])
            ->add('source', TextType::class, [
                'label' => 'Source',
                'required' => true,
            ])
            ->add('logoPath', TextType::class, [
                'label' => 'Logo Path',
                'required' => false,
            ])
            ->add('url', TextType::class, [
                'label' => 'URL',
                'required' => false,
            ])
            ->add('overviews', OverviewsType::class, [
                'data' => $options['overviews'],
                'watch_providers' => $options['watch_providers'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SerieAlternateOverview::class,
            'overviews' => [],
            'watch_providers' => [],
        ]);
        $resolver->setAllowedTypes('overviews', 'array');
        $resolver->setAllowedTypes('watch_providers', 'array');
    }
}

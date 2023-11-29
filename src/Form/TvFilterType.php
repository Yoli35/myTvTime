<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TvFilterType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
//        dump($options['data']);
        // - air_date.gte                      //date
        // - air_date.lte                      //date
        // * first_air_date_year               //int32
        // * first_air_date.gte                //date
        // * first_air_date.lte                //date
        // * include_adult                     //boolean
        // * include_null_first_air_dates      //boolean
        // * language                          //string
        // - page                              //int32
        // * screened_theatrically             //boolean
        // * sort_by                           //string        //Default: popularity.desc        //
        // * timezone                          //string
        // * vote_average.gte                  //float
        // * vote_average.lte                  //float
        // * vote_count.gte                    //float
        // * vote_count.lte                    //float
        // * watch_region                      //string
        // - with_companies                    //string        //can be a comma (AND) or pipe (OR) separated query
        // * with_genres                       //string        //can be a comma (AND) or pipe (OR) separated query
        // - with_keywords                     //string        //can be a comma (AND) or pipe (OR) separated query
        // - with_networks                     //int32
        // * with_origin_country               //string
        // * with_original_language            //string
        // * with_runtime.gte                  //int32
        // * with_runtime.lte                  //int32
        // * with_status                       //string        //possible values are: [0, 1, 2, 3, 4, 5], can be a comma (AND) or pipe (OR) separated query
        // * with_watch_monetization_types     //string        //possible values are: [flatrate, free, ads, rent, buy] use in conjunction with watch_region, can be a comma (AND) or pipe (OR) separated query
        // * with_watch_providers              //string        //use in conjunction with watch_region, can be a comma (AND) or pipe (OR) separated query
        //without_companies                 //string
        //without_genres                    //string
        //without_keywords                  //string
        //without_watch_providers           //string
        //with_type                         //string        //possible values are: [0, 1, 2, 3, 4, 5, 6], can be a comma (AND) or pipe (OR) separated query
        $builder
            ->add('sort_by', ChoiceType::class, [
                'label' => 'Series displayed by',
                'choices' => [
                    'Popularity' => 'popularity',
                    'Revenue' => 'revenue',
                    'Primary release date' => 'primary_release_date',
                    'Vote average' => 'vote_average',
                    'Vote count' => 'vote_count',
                ],
                'required' => true,
            ])
            ->add('order_by', ChoiceType::class, [
                'label' => '',
                'choices' => [
                    'Ascending' => 'asc',
                    'Descending' => 'desc',
                ],
                'required' => true,
            ])

            ->add('switch_with_status', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_status', ChoiceType::class, [
                'label' => $this->translator->trans('Status'),
                'choices' => [
                    $this->translator->trans('Returning Series') => '0',
                    $this->translator->trans('Planned') => '1',
                    $this->translator->trans('In Production') => '2',
                    $this->translator->trans('Ended') => '3',
                    $this->translator->trans('Canceled') => '4',
                    $this->translator->trans('Pilot') => '5',
                ],
                'required' => false,
            ])

            ->add('switch_watch_region', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('watch_region', ChoiceType::class, [
                'label' => 'Watch region',
                'choices' => $options['data']['watch_regions'],
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('switch_with_watch_monetization_types', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_watch_monetization_types', ChoiceType::class, [
                'label' => 'Type of monetization',
                'choices' => [
                    'Flatrate' => 'flatrate',
                    'Free' => 'free',
                    'Ads' => 'ads',
                    'Rent' => 'rent',
                    'Buy' => 'buy',
                ],
                'required' => false,
            ])
            ->add('switch_with_watch_providers', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_watch_providers', ChoiceType::class, [
                'label' => 'Watch provider',
                'choices' => $options['data']['watch_providers'],
                'expanded' => false,
                'multiple' => false,
            ])

            ->add('switch_with_origin_country', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_origin_country', CountryType::class, [
                'label' => $this->translator->trans('Origin country'),
                'required' => false,
            ])
            ->add('switch_with_original_language', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_original_language', LanguageType::class, [
                'label' => $this->translator->trans('Original language'),
                'required' => false,
            ])

            ->add('switch_with_genres', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_genres', ChoiceType::class, [
                'choices' => $options['data']['genres'],
                'expanded' => true,
                'multiple' => true,
            ])

            ->add('switch_first_air_date_year', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('first_air_date_year', NumberType::class, [
                'label' => $this->translator->trans('Air date year'),
                'required' => false,
            ])
            ->add('switch_first_air_date_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('first_air_date_gte', DateType::class, [
                'label' => $this->translator->trans('After'),
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                ],
            ])
            ->add('switch_first_air_date_lte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('first_air_date_lte', DateType::class, [
                'label' => $this->translator->trans('Before'),
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                ],
            ])
            ->add('switch_include_null_first_air_date', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('include_null_first_air_date', CheckboxType::class, [
                'label' => $this->translator->trans('No date'),
                'required' => false,
            ])

            ->add('switch_language', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('language', LanguageType::class, [
                'label' => $this->translator->trans('Language'),
                'required' => false,
            ])
            ->add('switch_timezone', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => $this->translator->trans('Timezone'),
                'required' => false,
            ])

            ->add('switch_vote_average_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_average_gte', NumberType::class, [
                'label' => $this->translator->trans('Vote average greater than'),
                'required' => false,
            ])
            ->add('switch_vote_average_lte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_average_lte', NumberType::class, [
                'label' => $this->translator->trans('Vote average less than'),
                'required' => false,
            ])
            ->add('switch_vote_count_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_count_gte', NumberType::class, [
                'label' => $this->translator->trans('Vote count greater than'),
                'required' => false,
            ])
            ->add('switch_vote_count_lte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_count_lte', NumberType::class, [
                'label' => $this->translator->trans('Vote count less than'),
                'required' => false,
            ])

            ->add('switch_with_runtime_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_runtime_gte', NumberType::class, [
                'label' => $this->translator->trans('Runtime greater than'),
                'required' => false,
            ])
            ->add('switch_with_runtime_lte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_runtime_lte', NumberType::class, [
                'label' => $this->translator->trans('Runtime less than'),
                'required' => false,
            ])

            ->add('switch_screened_theatrically', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('screened_theatrically', CheckboxType::class, [
                'label' => $this->translator->trans('Screened theatrically'),
                'required' => false,
                'value' => false,
            ])

            ->add('switch_include_adult', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('include_adult', CheckboxType::class, [
                'label' => $this->translator->trans('Adult'),
                'required' => false,
                'value' => false,
            ])
            ;
    }
}
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
        //with_type                         //string        //possible values are: [0 Documentary, 1 News, 2 Miniseries, 3 Reality, 4 Scripted, 5 Talk Show, 6 Video], can be a comma (AND) or pipe (OR) separated query
        $builder
            ->add('sort_by', ChoiceType::class, [
                'label' => 'Series displayed by',
                'choices' => [
                    'Popularity' => 'popularity',
                    'Revenue' => 'revenue',
                    'Primary release date' => 'first_air_date',
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
                'label' => 'Status',
                'choices' => [
                    'Returning Series' => '0',
                    'Planned' => '1',
                    'In Production' => '2',
                    'Ended' => '3',
                    'Canceled' => '4',
                    'Pilot' => '5',
                ],
                'required' => false,
            ])
            ->add('switch_with_type', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Documentary' => '0',
                    'News' => '1',
                    'Miniseries' => '2',
                    'Reality' => '3',
                    'Scripted' => '4',
                    'Talk Show' => '5',
                    'Video' => '6',
                ],
                'choice_attr' => [
                    'Documentary' => ['data-title' => 'e.g. wildlife documentary'],
                    'News' => ['data-title' => 'e.g. news, political programmes'],
                    'Miniseries' => ['data-title' => 'miniseries'],
                    'Reality' => ['data-title' => 'reality'],
                    'Scripted' => ['data-title' => 'scripted'],
                    'Talk Show' => ['data-title' => 'talk-show'],
                    'Video' => ['data-title' => 'video'],
                ],
                'required' => false,
            ])

            ->add('switch_watch_region', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('watch_region', ChoiceType::class, [
                'label' => 'Watch region',
                'choices' => $options['data']['watchRegionSelect'],
                'choice_translation_domain' => false,
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
                'choices' => $options['data']['watchProviderSelect'],
                'choice_translation_domain' => false,
                'expanded' => false,
                'multiple' => false,
            ])

            ->add('switch_with_origin_country', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_origin_country', CountryType::class, [
                'label' => 'Origin country',
                'required' => false,
            ])
            ->add('switch_with_original_language', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_original_language', LanguageType::class, [
                'label' => 'Original language',
                'required' => false,
            ])

            ->add('switch_with_genres', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_genres', ChoiceType::class, [
                'choices' => $options['data']['genreSelect'],
                'expanded' => true,
                'multiple' => true,
            ])

            ->add('switch_first_air_date_year', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('first_air_date_year', NumberType::class, [
                'attr' =>[
                    'size' => '4',
                ],
                'label' => 'Air date year',
                'required' => false,
            ])
            ->add('switch_first_air_date_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('first_air_date_gte', DateType::class, [
                'label' => 'After',
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
                'label' => 'Before',
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
                'label' => 'No date',
                'required' => false,
            ])

            ->add('switch_language', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('language', LanguageType::class, [
                'label' => 'Language',
                'required' => false,
            ])
            ->add('switch_timezone', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'Timezone',
                'required' => false,
            ])

            ->add('switch_vote_average_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_average_gte', NumberType::class, [
                'attr' =>[
                    'size' => '3',
                ],
                'label' => 'Vote average greater than',
                'required' => false,
            ])
            ->add('switch_vote_average_lte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_average_lte', NumberType::class, [
                'attr' =>[
                    'size' => '3',
                ],
                'label' => 'Vote average less than',
                'required' => false,
            ])
            ->add('switch_vote_count_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_count_gte', NumberType::class, [
                'attr' =>[
                    'size' => '3',
                ],
                'label' => 'Vote count greater than',
                'required' => false,
            ])
            ->add('switch_vote_count_lte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('vote_count_lte', NumberType::class, [
                'attr' =>[
                    'size' => '3',
                ],
                'label' => 'Vote count less than',
                'required' => false,
            ])

            ->add('switch_with_runtime_gte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_runtime_gte', NumberType::class, [
                'label' => 'Runtime greater than',
                'required' => false,
            ])
            ->add('switch_with_runtime_lte', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('with_runtime_lte', NumberType::class, [
                'label' => 'Runtime less than',
                'required' => false,
            ])

            ->add('switch_screened_theatrically', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('screened_theatrically', CheckboxType::class, [
                'label' => 'Screened theatrically',
                'required' => false,
                'value' => false,
            ])

            ->add('switch_include_adult', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('include_adult', CheckboxType::class, [
                'label' => 'Adult',
                'required' => false,
                'value' => false,
            ])

            ->add('switch_page', CheckboxType::class, [
                'attr' => [
                    'class' => 'd-none',
                ],
                'label' => '',
                'required' => false,
                'value' => true,
            ])
            ->add('page', NumberType::class, [
                'attr' =>[
                    'size' => '3',
                ],
                'label' => 'Page',
                'required' => false,
            ])
            ;
    }
}
<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class MovieByNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = intval(date("Y"));
        $years = [];
        for ($i = $now; $i >= 1874; $i--) {
            $years[$i] = $i;
        }

        $builder
            ->add('query', SearchType::class, [
                'required' => false,
                'attr' => [
                    'placeHolder' => 'Enter a movie name'
                ],
            ])
            ->add('year', SearchType::class, [
                'required' => false,
                'attr' => [
                    'placeHolder' => 'Enter a year'
                ]
            ])
            ->add('save', SubmitType::class);
    }
}
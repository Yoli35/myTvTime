<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MovieByNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = intval(date("Y"));
        $years = ['Select a Year' => 'none'];
        for ($i = $now; $i >= 1874; $i--) {
            $years[$i] = $i;
        }

        $builder
            ->add('query', TextType::class, ['required' => false])
            ->add('year', ChoiceType::class, ['choices' => $years])
            ->add('save', SubmitType::class)
            ;
    }
}
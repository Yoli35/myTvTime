<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MovieByNameType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('query', SearchType::class, [
                'required' => false,
                'attr' => [
                    'placeHolder' => $this->translator->trans('Enter a movie name')
                ],
            ])
            ->add('year', SearchType::class, [
                'required' => false,
                'attr' => [
                    'placeHolder' => $this->translator->trans('Enter a year')
                ]
            ])
            ->add('save', SubmitType::class);
    }
}
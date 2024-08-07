<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SerieSearchType extends AbstractType
{
    private TranslatorInterface $translator;
    public function __construct (TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', SearchType::class, [
                'required' => false,
                'attr' => [
                    'placeHolder' => $this->translator->trans('Enter a Serie name'),
                ],
            ])
            ->add('year', SearchType::class, [
                'required' => false,
                'attr' => [
                    'placeHolder' => $this->translator->trans('Enter a year'),
                ]
            ])
            ->add('search', SubmitType::class, [
//                'label' => $this->translator->trans('Search'),
            ]);
    }
}
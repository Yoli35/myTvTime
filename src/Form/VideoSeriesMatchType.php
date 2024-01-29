<?php

namespace App\Form;

use App\Entity\VideoSeriesMatch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VideoSeriesMatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('active', CheckboxType::class, [
                'label' => '',
                'required' => false,
            ])
            ->add('expr', TextType::class, [
                'label' => 'Expr',
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
            ])
            ->add('position', NumberType::class, [
                'label' => 'Position',
                'required' => true,
            ])
            ->add('occurrence', NumberType::class, [
                'label' => 'Occurrence',
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'String' => 'VARCHAR',
                    'Number' => 'UNSIGNED',
                ],
                'required' => true,
            ])
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VideoSeriesMatch::class,
        ]);
    }
}
<?php

namespace App\Form;

use App\Entity\ActivityDay;
use App\Form\Type\StandUpType;
use Doctrine\DBAL\Types\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityDayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('standUpRingCompleted', CheckboxType::class, [
                'label' => 'Stand Up Ring Completed',
                'required' => true,
            ])
            ->add('moveRingCompleted', CheckboxType::class, [
                'label' => 'Move Ring Completed',
                'required' => true,
            ])
            ->add('exerciseRingCompleted', CheckboxType::class, [
                'label' => 'Exercise Ring Completed',
                'required' => true,
            ])
            ->add('standUp', StandUpType::class, [
                'label' => 'Stand Up',
                'required' => true,
            ])
            ->add('standUpResult', IntegerType::class, [
                'label' => 'Stand Up Result',
                'required' => true,
            ])
            ->add('moveResult', IntegerType::class, [
                'label' => 'Move Result (KCal)',
                'required' => true,
            ])
            ->add('exerciseResult', IntegerType::class, [
                'label' => 'Exercise Result (minutes)',
                'required' => true,
            ])
            ->add('steps', IntegerType::class, [
                'label' => 'Steps',
                'required' => true,
            ])
            ->add('distance', NumberType::class, [
                'label' => 'Distance (kilometers)',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivityDay::class,
        ]);
    }
}

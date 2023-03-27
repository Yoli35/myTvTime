<?php

namespace App\Form;

use App\Entity\Activity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('standUpGoal', IntegerType::class, [
                'label' => 'Stand Up Goal',
                'required' => true,
            ])
            ->add('moveGoal', IntegerType::class, [
                'label' => 'Move Goal (KCal)',
                'required' => true,
            ])
            ->add('exerciseGoal', IntegerType::class, [
                'label' => 'Exercise Goal (minutes)',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\ActivityChallenge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActivityChallengeType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class', "w100"],
                'required' => true,
            ])
            ->add('challenge', ChoiceType::class, [
                'label' => 'Challenge',
                'attr' => ['class', "w100"],
                'choices' => [
                    'Distance covered' => 'distance',
                    'Exercise duration' => 'exercise_result',
                    'Move challenge' => 'move_result',
                    'Number of steps' => 'steps',
                ],
                'placeholder' => 'Choose a challenge',
                'required' => true,
            ])
            ->add('value', NumberType::class, [
                'label' => 'Quantity',
                'attr' => ['class', "w100"],
                'required' => true,
            ])
            ->add('goal', IntegerType::class, [
                'label' => 'Goal',
                'attr' => ['class', "w100"],
                'required' => true,
            ])
            ->add('startAt', DateType::class, [
                'label' => 'Start date',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable'
            ])
            ->add('endAt', DateType::class, [
                'label' => 'End date',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable'
            ])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'btn btn-secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivityChallenge::class,
        ]);
    }
}

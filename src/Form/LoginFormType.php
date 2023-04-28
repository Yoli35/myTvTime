<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Email address',
                    'aria-label' => 'Email',
                    'aria-describedby' => 'Email',
                ]
            ])
            ->add('password', PasswordType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'Enter your password',
                    'aria-label' => 'Password',
                    'aria-describedby' => 'Password'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

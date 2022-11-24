<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;

class UserType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'w100'],
                'required' => true,
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => ['class' => 'w100'],
                'required' => true,
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'attr' => ['class' => 'w100'],
                'required' => false,
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Zip code',
                'attr' => ['class' => 'w100'],
                'required' => false,
            ])
            ->add('country', CountryType::class, [ //] TextType::class, [
                'label' => 'Country',
                'attr' => ['class' => 'form-select w100'],
                'required' => false,
                'preferred_choices' => ['FR', 'DE', 'GB', 'ES', 'US'],
            ])
            ->add('preferredLanguage', ChoiceType::class, [
                'label' => 'Preferred language',
                'attr' => ['class' => 'form-select w100'],
                'choices' => [
                    'French' => 'fr',
                    'English' => 'en',
                    'German' => 'de',
                    'Spanish' => 'es'
                ],
                'expanded' => false,
            ])
            ->add('dropThumbnail', DropzoneType::class, [
                'label' => 'Profile Image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'w100',
                    'placeholder' => $this->translator->trans('Drop a profile file'),
                    'accept' => 'image/*'
                ],
            ])
            ->add('dropBanner', DropzoneType::class, [
                'label' => 'Banner Image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'mydropzone',
                    'class' => 'w100',
                     'placeholder' => $this->translator->trans('Drop a banner file'),
                    'accept' => 'image/*'
               ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Update Profile',
                'attr' => ['class' => 'btn btn-secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

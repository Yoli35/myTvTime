<?php

namespace App\Form;

use App\Entity\YoutubeVideo;
use App\Entity\YoutubeVideoTag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class YoutubeVideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tags', ChoiceType::class, [
                'multiple' => true,
                'required' => false,
/*                'choice_name' => ChoiceList::fieldName($this, function () {
                    return 'label';
                }),
                'choice_value' => ChoiceList::value($this, function () {
                    return 'id';
                }),*/
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => YoutubeVideo::class,
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\BookInteraction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InlineInteractionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('finished')
            ->add('favorite')
            ->add('finishedDate', null, [
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookInteraction::class,
            'csrf_protection' => false,
        ]);
    }
}

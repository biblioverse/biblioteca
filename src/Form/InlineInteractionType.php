<?php

namespace App\Form;

use App\Entity\BookInteraction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InlineInteractionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('finished', CheckboxType::class, [
                'required' => false,
            ])
            ->add('favorite', CheckboxType::class, [
                'required' => false,
            ])
            ->add('finishedDate', null, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookInteraction::class,
            'csrf_protection' => false,
        ]);
    }
}

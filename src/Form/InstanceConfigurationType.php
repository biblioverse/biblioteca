<?php

namespace App\Form;

use App\Entity\InstanceConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstanceConfigurationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'disabled' => true,
            ])
            ->add('value')
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InstanceConfiguration::class,
            'label_translation_prefix' => 'instance-configuration.form.',
        ]);
    }
}

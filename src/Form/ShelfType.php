<?php

namespace App\Form;

use App\Entity\Shelf;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShelfType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
        ;

        if ($options['data'] instanceof Shelf && $options['data']->getQueryString() !== null) {
            $builder->add('queryString');
            $builder->add('queryFilter');
            $builder->add('queryOrder');
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shelf::class,
        ]);
    }
}

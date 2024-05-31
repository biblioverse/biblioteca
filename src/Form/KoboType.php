<?php

namespace App\Form;

use App\Entity\KoboDevice;
use App\Entity\Shelf;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KoboType extends AbstractType
{
    public function __construct(protected Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('accessKey')
            ->add('forceSync', null, [
                'label' => 'Force Sync',
                'required' => false,
            ]);
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
            ]);
        }
        $builder->add('shelves', EntityType::class, [
            'label' => 'Sync with Shelves',
            'class' => Shelf::class,
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KoboDevice::class,
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\KoboDevice;
use App\Entity\Shelf;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        $builder->add('shelves', EntityType::class, [
            'label' => 'Sync with Shelves',
            'class' => Shelf::class,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->setParameter('user', $this->security->getUser())
                    ->andWhere('u.user = :user');
            },
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

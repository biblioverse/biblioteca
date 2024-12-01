<?php

namespace App\Form;

use App\Entity\KoboDevice;
use App\Entity\Shelf;
use App\Kobo\Proxy\KoboProxyConfiguration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KoboType extends AbstractType
{
    public function __construct(
        protected Security $security,
        protected KoboProxyConfiguration $koboProxyConfiguration,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('accessKey')
            ->add('deviceId', null, [
                'label' => 'Device ID',
                'disabled' => true,
                'required' => false,
            ])
            ->add('model', null, [
                'label' => 'Model',
                'disabled' => true,
                'required' => false,
            ])
            ->add('forceSync', null, [
                'label' => 'Force Sync',
                'required' => false,
            ])
            ->add('upstreamSync', null, [
                'label' => 'Sync books with the official store too',
                'required' => false,
                'disabled' => !$this->koboProxyConfiguration->useProxy(),
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

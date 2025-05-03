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

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('accessKey')
            ->add('deviceId', null, [
                'disabled' => true,
                'required' => false,
            ])
            ->add('model', null, [
                'disabled' => true,
                'required' => false,
            ]);

        if (is_bool($options['show_last_sync_token']) && $options['show_last_sync_token']) {
            $builder->add('lastSyncToken', KoboLastSyncTokenType::class);
        }

        $builder->add('forceSync', null, [
            'required' => false,
            'help' => 'kobo.form.force_sync_help',
        ])
            ->add('upstreamSync', null, [
                'required' => false,
                'help' => 'kobo.form.upstreamsync_help',
                'disabled' => !$this->koboProxyConfiguration->useProxy(),
            ])->add('syncReadingList', null, [
                'required' => false,
                'help' => 'kobo.form.syncreadinglist_help',
            ]);

        $builder->add('shelves', EntityType::class, [
            'class' => Shelf::class,
            'help' => 'kobo.form.shelvessync_help',
            'query_builder' => fn (EntityRepository $er): QueryBuilder => $er->createQueryBuilder('u')
                ->setParameter('user', $this->security->getUser())
                ->andWhere('u.user = :user'),
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
        ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_last_sync_token' => false,
            'data_class' => KoboDevice::class,
            'label_translation_prefix' => 'kobo.form.',
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'user.form.roles.user' => 'ROLE_USER',
                    'user.form.roles.admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'label' => 'user.form.roles',
                'translation_domain' => false,
            ])
            ->add('maxAgeCategory', ChoiceType::class, ['choices' => User::AGE_CATEGORIES, 'required' => false])
            ->add('language', ChoiceType::class, [
                'choices' => [
                    'user.form.language.english' => 'en',
                    'user.form.language.french' => 'fr',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'help' => 'user.form.leave-blank-to-keep-current-password',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'label_translation_prefix' => 'user.form.',
        ]);
    }
}

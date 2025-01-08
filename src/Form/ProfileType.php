<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class ProfileType extends AbstractType
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displaySeries')
            ->add('displayAuthors')
            ->add('displayTags')
            ->add('displayPublishers')
            ->add('displayTimeline')
            ->add('displayAllBooks')
            ->add('language', ChoiceType::class, [
                'choices' => [
                    'profile.form.language.english' => 'en',
                    'profile.form.language.french' => 'fr',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => false,
                'first_options' => [],
                'second_options' => [],
            ])
            ->add('bookKeywordPrompt', null, [
                'help_html' => true,
                'help' => 'ai.bookprompt.form-help',
            ])
            ->add('bookSummaryPrompt', null, [
                'help_html' => true,
                'help' => 'ai.summaryprompt.form-help',
            ])
            ->add('theme', ChoiceType::class, [
                'choices' => [
                    'profile.form.theme.default' => 'default',
                    'profile.form.theme.dark' => 'dark',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
            ->setAction($this->router->generate('app_user_profile'))
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'label_translation_prefix' => 'profile.form.',
        ]);
    }
}

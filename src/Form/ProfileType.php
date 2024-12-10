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

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displaySeries')
            ->add('displayAuthors')
            ->add('displayTags')
            ->add('displayPublishers')
            ->add('displayTimeline')
            ->add('displayAllBooks')
            ->add('useKoboDevices')
            ->add('language', ChoiceType::class, [
                'choices' => [
                    'English' => 'en',
                    'French' => 'fr',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => false,
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
            ])
            ->add('openAIKey', null, [
                'label' => 'OpenAI Key',
                'help' => 'openai.help',
                'help_html' => true,
            ])
            ->add('bookKeywordPrompt', null, [
                'label' => 'Book Keyword Prompt',
                'help_html' => true,
                'help' => 'openai.bookprompt.help',
            ])
            ->add('bookSummaryPrompt', null, [
                'label' => 'Book Summary Prompt',
                'help_html' => true,
                'help' => 'openai.summaryprompt.help',
            ])
            ->add('theme', ChoiceType::class, [
                'label' => 'Theme',
                'choices' => [
                    'Default' => 'default',
                    'Dark' => 'dark',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
            ->setAction($this->router->generate('app_user_profile'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class ProfileType extends AbstractType
{
    public function __construct(private RouterInterface $router)
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
            ->add('openAIKey')
            ->add('bookKeywordPrompt', null, [
                'label' => 'Book Keyword Prompt',
                'help_html' => true,
                'help' => 'This is the prompt that will be used to generate keywords for books. It should be a short sentence or phrase. Here is a good starting example: <br>
<code>Can you give 5 classifying keywords about the book {book} in a list without explanation in french</code>',
            ])
            ->add('bookSummaryPrompt', null, [
                'label' => 'Book Summary Prompt',
                'help_html' => true,
                'help' => 'This is the prompt that will be used to generate a summary for books. It should be a short sentence or phrase. Here is a good starting example: <br>
<code>Can you make a factual summary of the book {book} in around 150 words in french</code>',
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

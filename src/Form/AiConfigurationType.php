<?php

namespace App\Form;

use App\Entity\AiModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AiConfigurationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('AI_SEARCH_MODEL', EntityType::class, [
                'class' => AiModel::class,
                'placeholder' => 'ai.searchmodel.form-placeholder',
                'required' => false,
                'choice_label' => 'label',
            ])
            ->add('AI_ASSISTANT_MODEL', EntityType::class, [
                'class' => AiModel::class,
                'placeholder' => 'ai.assistantmodel.form-placeholder',
                'required' => false,
                'choice_label' => 'label',
            ])
            ->add('AI_CONTEXT_MODEL', EntityType::class, [
                'class' => AiModel::class,
                'placeholder' => 'ai.contextmodel.form-placeholder',
                'required' => false,
                'choice_label' => 'label',
                'help' => 'ai.contextmodel.form-help',
            ])
            ->add('AI_SUMMARY_PROMPT', TextareaType::class, [
                'required' => false,
                'help' => 'ai.summaryprompt.form-help',
            ])
            ->add('AI_TAG_PROMPT', TextareaType::class, [
                'required' => false,
                'help' => 'ai.bookprompt.form-help',
            ])->add('submit', SubmitType::class)

        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label_translation_prefix' => 'ai.configuration.form.',
        ]);
    }
}

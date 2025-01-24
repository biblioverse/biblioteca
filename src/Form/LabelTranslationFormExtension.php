<?php

namespace App\Form;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class LabelTranslationFormExtension extends AbstractTypeExtension
{
    public const string DEFAULT_TRANSLATION_PREFIX = 'form.';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param array<string,mixed> $options
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationPrefix = $options['label_translation_prefix'];
        if (!is_string($translationPrefix)) {
            $translationPrefix = '';
        }

        $label = $builder->getOption('label');

        if (!is_string($label)) {
            return;
        }

        if (!str_contains($label, $translationPrefix)) {
            $this->logger->warning('Label for '.$builder->getName().' is automatically generated within the LabelTranslationFormExtension, please do not set it manually.');
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label_translation_prefix' => self::DEFAULT_TRANSLATION_PREFIX,
        ]);
        $resolver->setAllowedTypes('label_translation_prefix', 'string');
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class, ButtonType::class, SubmitType::class];
    }

    /**
     * @param array{'label_translation_prefix': string, 'translation_domain': string|false}|array<string, mixed> $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['translation_domain'] === false) {
            return;
        }

        $prefix = $options['label_translation_prefix'];
        if (!$form->isRoot()) {
            $prefix = $form->getRoot()->getConfig()->getOption('label_translation_prefix');
        }
        if (!is_string($prefix)) {
            $prefix = '';
        }

        $view->vars['label'] = new TranslatableMessage($prefix.strtolower($form->getName()));
    }
}

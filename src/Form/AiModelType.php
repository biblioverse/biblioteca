<?php

namespace App\Form;

use App\Ai\Communicator\AiCommunicatorInterface;
use App\Entity\AiModel;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AiModelType extends AbstractType
{
    private array $taggedServices = [];

    /**
     * @param AiCommunicatorInterface[] $services
     */
    public function __construct(#[AutowireIterator('app.ai_communicator')] iterable $services)
    {
        foreach ($services as $service) {
            $this->taggedServices[] = $service::class;
        }
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => array_combine($this->taggedServices, $this->taggedServices),
            ])
            ->add('url')
            ->add('model')
            ->add('token', null, ['required' => false])
            ->add('useAmazonContext', null, ['required' => false])
            ->add('useEpubContext', null, ['required' => false])
            ->add('useWikipediaContext', null, ['required' => false])
            ->add('systemPrompt', null, ['required' => true])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AiModel::class,
            'label_translation_prefix' => 'ai.form.',
        ]);
    }
}

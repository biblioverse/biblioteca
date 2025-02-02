<?php

namespace App\Form;

use App\Entity\BookInteraction;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class InlineInteractionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('readingList', EnumType::class, [
                'class' => ReadingList::class,
            ])
            ->add('readStatus', EnumType::class, [
                'class' => ReadStatus::class,
            ])
            ->add('rating', IntegerType::class, [
                'attr' => [
                    'min' => 0, // Sets the HTML5 min attribute
                    'max' => 5, // Sets the HTML5 max attribute
                ],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 5,
                        'notInRangeMessage' => 'Rating must be between {{ min }} and {{ max }}.',
                    ]),
                ],
            ])
            ->add('finishedDate', null, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookInteraction::class,
            'csrf_protection' => false,
            'label_translation_prefix' => 'interaction.form.',
        ]);
    }
}

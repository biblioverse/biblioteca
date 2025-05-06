<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KoboLastSyncTokenType extends TextType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'disabled' => true,
            'attr' => [
                'readonly' => true,
                'disabled' => true,
            ],
        ]);
    }
}

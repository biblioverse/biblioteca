<?php

namespace App\Form;

use App\Entity\EreaderEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EreaderEmailType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'ereader_email.form.name',
                'help' => 'ereader_email.form.name_help',
            ])
            ->add('email', EmailType::class, [
                'label' => 'ereader_email.form.email',
                'help' => 'ereader_email.form.email_help',
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EreaderEmail::class,
            'label_translation_prefix' => 'ereader_email.form.',
        ]);
    }
}

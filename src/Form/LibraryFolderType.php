<?php

namespace App\Form;

use App\Entity\LibraryFolder;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LibraryFolderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('folder', FolderPickerType::class)
            ->add('icon')
            ->add('folderNamingFormat', TextType::class, ['required' => false, 'attr' => ['placeholder' => '{authorFirst}/{author}/{serie}/{title}'], 'help' => 'profile.folder.folderNamingFormat.help'])
            ->add('fileNamingFormat', TextType::class, ['required' => false, 'attr' => ['placeholder' => '{serie}-{serieIndex}-{title}'], 'help' => 'profile.folder.fileNamingFormat.help'])
            ->add('defaultLibrary')
            ->add('autoRelocation')
            ->add('volumeIdentifier', TextType::class, ['required' => false, 'attr' => ['placeholder' => 'T'], 'help' => 'profile.folder.volumeIdentifier.help'])
            ->add('allowedUsers', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'expanded' => true,
                'multiple' => true,
                'help' => 'profile.folder.allowedUsers.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LibraryFolder::class,
            'label_translation_prefix' => 'profile.folder.',
        ]);
    }
}

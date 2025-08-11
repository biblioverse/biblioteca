<?php

namespace App\Form;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FolderPickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $finder = new Finder();
        $folders = [];
        foreach ($finder
                     ->in('/')
                     ->ignoreUnreadableDirs(true)->ignoreVCS(true)
            ->ignoreDotFiles(true)
                     ->exclude(['etc', 'proc', 'root', 'var/cache', 'var/spool', 'node_modules', 'opt', 'sys', 'dev', 'tmp', 'usr', 'run', 'boot'])
                     ->directories()->sortByName()->getIterator() as $directory) {
            $folders[$directory->getRealPath()] = $directory->getRealPath();
        }

        $resolver->setDefaults([
            'choices' => $folders,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}

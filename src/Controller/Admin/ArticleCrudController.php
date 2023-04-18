<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setPageTitle('index', 'Tous les %entity_label_plural%')
            ->setPageTitle('new', 'Nouvel article')
            ->setDefaultSort(['created' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateField::new('created','Créé le')->onlyOnIndex(),
            TextField::new('title','Titre')->setTemplatePath('admin/field/linkedit.html.twig'),
            TextField::new('heading','En-tête')
                ->hideOnIndex(),
            TextEditorField::new('body')
                ->setRequired(true)
                ->hideOnIndex(),
            ImageField::new('image')
                ->setUploadDir('public/images')
                ->setBasePath('/images')
                ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]'),
            BooleanField::new('pinned','Exergue')->renderAsSwitch(false),
            BooleanField::new('published','Publié')->renderAsSwitch(false),
            ChoiceField::new('type')
                ->setChoices(['choices' => ['event' => 'event', 'news' => 'news', 'book' => 'book']]),
            DateField::new('eventdate', "Date de l'événement (si c'est un événement)")
                ->hideOnIndex(),
        ];
    }

}

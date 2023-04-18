<?php

namespace App\Controller\Admin;

use App\Entity\Library;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LibraryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Library::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Point de vente')
            ->setEntityLabelInPlural('Points de vente')
            ->setPageTitle('index', 'Tous les %entity_label_plural%')
            ->setPageTitle('new', 'Nouveau point de vente')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')
                ->setTemplatePath('admin/field/linkedit.html.twig'),
            TextEditorField::new('schedule')
                ->setRequired(true)
                ->hideOnIndex(),
            TextEditorField::new('body')
                ->setRequired(true)
                ->hideOnIndex(),
            TextField::new('phone')
                ->hideOnIndex(),
            TextField::new('email')
                ->hideOnIndex(),
            ImageField::new('image')
                ->setUploadDir('public/images/librairies')
                ->setBasePath('/images/librairies')
                ->setUploadedFileNamePattern('[slug].[extension]'),
            BooleanField::new('canOrderOnOrderPage')->renderAsSwitch(false),
        ];
    }

}

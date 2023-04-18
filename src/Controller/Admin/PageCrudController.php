<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Page')
            ->setEntityLabelInPlural('Pages')
            ->setPageTitle('index', 'Toutes les %entity_label_plural%')
            ->setPageTitle('new', 'Nouvelle page')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title')
                ->setTemplatePath('admin/field/linkedit.html.twig'),
            TextEditorField::new('body')
                ->setRequired(true)
                ->hideOnIndex(),
            ImageField::new('image')
                ->setUploadDir('public/images/librairies')
                ->setBasePath('/images/librairies')
                ->setUploadedFileNamePattern('[slug].[extension]')
        ];
    }

}

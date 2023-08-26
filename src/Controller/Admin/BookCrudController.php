<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BookCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Book')
            ->setEntityLabelInPlural('Books')
            ->setPageTitle('index', 'All %entity_label_plural%')
            ->setDefaultSort(['created' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title',)->setTemplatePath('admin/field/linkedit.html.twig'),
            TextEditorField::new('summary',)->hideOnIndex(),
            TextField::new('serie',),
            NumberField::new('serieIndex',),
            TextField::new('mainAuthor',),
            TextField::new('language',),
            TextField::new('publisher',)->hideOnIndex(),
            DateField::new('publishDate',)->hideOnIndex(),
            ArrayField::new('authors',)->setRequired(false)->hideOnIndex(),
            DateField::new('created',)->onlyOnIndex(),

        ];
    }

}

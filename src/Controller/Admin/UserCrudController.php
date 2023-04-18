<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Utilisatrice/eur')
            ->setEntityLabelInPlural('Utilisatrices/eurs')
            ->setPageTitle('index', 'Toutes les %entity_label_plural%')
            ->setPageTitle('new', 'Nouveau')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('username','Nom pour se connecter')->setTemplatePath('admin/field/linkedit.html.twig'),
            TextField::new('displayName','Nom à afficher'),
            EmailField::new('email')
                ->setRequired(true),
            TextField::new('password')->setFormType(PasswordType::class)->onlyOnForms(),
        ];
    }

}

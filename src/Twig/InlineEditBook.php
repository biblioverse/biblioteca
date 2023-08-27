<?php
namespace App\Twig;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent()]
class InlineEditBook extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;


    #[LiveProp(writable: ['title','serie', 'serieIndex', 'mainAuthor'])]
    public Book $book;

    #[LiveProp()]
    public bool $isEditing = false;

    #[LiveProp()]
    public string $field;

    public ?string $flashMessage = null;

    #[LiveAction]
    public function activateEditing():void
    {
        $this->isEditing = true;
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager):void
    {

        $entityManager->flush();

        $this->isEditing = false;


        $this->flashMessage = ' book updated';
    }
}

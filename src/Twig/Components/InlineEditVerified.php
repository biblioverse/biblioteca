<?php

namespace App\Twig\Components;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(method: 'get')]
class InlineEditVerified extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: ['verified'])]
    public Book $book;

    public ?string $flashMessage = null;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[LiveAction]
    #[LiveListener('submit')]
    public function save(): void
    {
        $this->entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');
        $this->flashMessage = ' book updated';
    }
}

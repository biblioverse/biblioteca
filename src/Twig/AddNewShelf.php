<?php

namespace App\Twig;

use App\Entity\Shelf;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent()]
class AddNewShelf extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    #[LiveProp()]
    public bool $isEditing = false;

    #[LiveProp()]
    public ?Shelf $shelf = null;

    #[LiveProp(writable: true)]
    public string $name = '';

    #[LiveProp()]
    public User $user;

    #[LiveAction]
    public function activateEditing(): void
    {
        if (null === $this->shelf) {
            $this->shelf = new Shelf();
        }
        $this->isEditing = true;
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        if (null === $this->shelf) {
            $this->shelf = new Shelf();
        }

        $this->name = trim($this->name);
        if ('' === $this->name) {
            return;
        }

        $this->shelf->setName($this->name);
        $this->shelf->setUser($this->user);

        $entityManager->persist($this->shelf);
        $entityManager->flush();

        $this->dispatchBrowserEvent('manager:flush');
        $this->isEditing = false;
    }
}

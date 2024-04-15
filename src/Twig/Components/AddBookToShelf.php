<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Entity\Shelf;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent()]
class AddBookToShelf extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp()]
    public Book $book;

    #[LiveProp()]
    public User $user;

    public ?string $flashMessage = null;

    /**
     * @var Shelf[]
     */
    public array $shelves = [];

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $shelfRepository = $entityManager->getRepository(Shelf::class);

        $this->shelves = $shelfRepository->findBy(['user' => $security->getUser()]);
        $this->shelves = array_filter($this->shelves, static function ($item) {
            return $item->getQueryString() === null;
        });
    }

    #[LiveAction]
    public function add(EntityManagerInterface $entityManager, #[LiveArg] int $shelf): void
    {
        $shelfRepository = $entityManager->getRepository(Shelf::class);

        $shelf = $shelfRepository->find($shelf);

        if (null === $shelf) {
            throw new \RuntimeException('Shelf not found');
        }

        $this->book->addShelf($shelf);

        $entityManager->flush();

        $this->flashMessage = 'Added to shelf';
    }

    #[LiveAction]
    public function remove(EntityManagerInterface $entityManager, #[LiveArg] int $shelf): void
    {
        $shelfRepository = $entityManager->getRepository(Shelf::class);

        $shelf = $shelfRepository->find($shelf);

        if (null === $shelf) {
            throw new \RuntimeException('Shelf not found');
        }

        $this->book->removeShelf($shelf);

        $entityManager->flush();

        $this->flashMessage = 'Removed from shelf';
    }
}

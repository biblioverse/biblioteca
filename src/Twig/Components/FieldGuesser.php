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

#[AsLiveComponent()]
class FieldGuesser extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    #[LiveProp()]
    public Book $book;

    public ?string $flashMessage = null;

    #[LiveAction]
    #[LiveListener('submit')]
    public function accept(EntityManagerInterface $entityManager): void
    {
        $this->book->setSerie($this->guessSerie());
        $this->book->setSerieIndex((float) $this->guessIndex());
        $this->book->setAuthors([$this->guessAuthor()]);
        $entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');

        $this->flashMessage = 'Saved';
    }

    #[LiveAction]
    #[LiveListener('submit')]
    public function acceptIndex(EntityManagerInterface $entityManager): void
    {
        $this->book->setSerieIndex((float) $this->guessIndex());
        $entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');

        $this->flashMessage = 'Saved';
    }

    #[LiveAction]
    #[LiveListener('submit')]
    public function acceptIndexAndRename(EntityManagerInterface $entityManager): void
    {
        $this->book->setSerieIndex((float) $this->guessIndex());
        $title = sprintf('T%02d', $this->guessIndex());
        $this->book->setTitle($title);
        $entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');

        $this->flashMessage = 'Saved';
    }

    public function guessSerie(): string
    {
        $author = implode('', $this->book->getAuthors());
        $parts = explode(' - ', $author);
        if (3 === count($parts)) {
            return $parts[1];
        }

        return '';
    }

    public function guessIndex(): string
    {
        $author = implode('', $this->book->getAuthors());
        $parts = explode(' - ', $author);
        if (3 === count($parts)) {
            return $parts[2];
        }

        $matches = [];
        preg_match('/(T|Volume |Vol. |Tome | v)(\d{1,3})/', $this->book->getTitle(), $matches);

        if ($matches !== []) {
            return $matches[2];
        }

        return '';
    }

    public function guessAuthor(): string
    {
        $author = implode('', $this->book->getAuthors());
        $parts = explode(' - ', $author);
        if (3 === count($parts)) {
            return $parts[0];
        }

        return '';
    }
}

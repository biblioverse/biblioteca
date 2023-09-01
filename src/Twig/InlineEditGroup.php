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

/**
 * @phpstan-type GroupType array{ item:string, slug:string, bookCount:int, booksFinished:int }
 */
#[AsLiveComponent()]
class InlineEditGroup extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    /**
     * @var GroupType
     */
    #[LiveProp(writable: ['item'])]
    public array $item;

    public bool $link = true;

    /**
     * @var GroupType
     */
    #[LiveProp()]
    public array $original;

    #[LiveProp()]
    public string $type;

    #[LiveProp()]
    public bool $isEditing = false;

    public ?string $flashMessage = null;

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    /**
     * @throws \RuntimeException
     */
    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        $thisItem = $this->item;

        $bookRepo = $entityManager->getRepository(Book::class);

        $books = $bookRepo->findBy([$this->type => $this->original['item']]);

        foreach ($books as $book) {
            switch ($this->type) {
                case 'mainAuthor':
                    $book->setMainAuthor($this->item['item']);
                    break;
                case 'serie':
                    $book->setSerie($this->item['item']);
                    break;
            }
            $entityManager->persist($book);
        }

        $entityManager->flush();

        $items = [];
        switch ($this->type) {
            case 'mainAuthor':
                $items = $bookRepo->getAllAuthors()->getResult();
                break;
            case 'serie':
                $items = $bookRepo->getAllSeries()->getResult();
                break;
        }

        if (!is_array($items)) {
            throw new \RuntimeException('No items found');
        }

        $item = array_filter($items, static function ($s) use ($thisItem) {
            return $s['item'] === $thisItem['item'];
        });

        $item = current($item);
        if (false === $item) {
            throw new \RuntimeException($this->type.' not found');
        }

        $this->original = $item;
        $this->item = $item;
        $this->isEditing = false;

        $this->flashMessage = count($books).' books updated';
    }
}

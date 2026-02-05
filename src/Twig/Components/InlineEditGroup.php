<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(method: 'get')]
class InlineEditGroup extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    #[LiveProp()]
    public string $existingValue;

    #[LiveProp(writable: true)]
    public string $fieldValue;

    #[LiveProp()]
    public bool $isEditing = false;

    #[LiveProp()]
    public string $field;

    public ?string $flashMessage = null;

    public function __construct(private readonly BookRepository $bookRepository)
    {
    }

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    /**
     * @throws \JsonException
     */
    #[LiveAction]
    public function remove(EntityManagerInterface $entityManager): void
    {
        $this->fieldValue = '';
        $this->save($entityManager);
    }

    /**
     * @throws \JsonException
     */
    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        $qb = $this->bookRepository->createQueryBuilder('book')
            ->select('book');
        $qb->andWhere('JSON_CONTAINS(lower(book.'.$this->field.'), :value)=1');
        $qb->setParameter('value', json_encode([strtolower($this->existingValue)], JSON_THROW_ON_ERROR));

        /** @var Book[] $books */
        $books = $qb->getQuery()->getResult();

        foreach ($books as $book) {
            switch ($this->field) {
                case 'authors':
                    $book->removeAuthor($this->existingValue);
                    if ($this->fieldValue !== '') {
                        $book->addAuthor($this->fieldValue);
                    }
                    break;
                case 'tags':
                    $book->removeTag($this->existingValue);
                    if ($this->fieldValue !== '') {
                        $book->addTag($this->fieldValue);
                    }
                    break;
                default:
                    throw new \RuntimeException('Field not implemented for group edition');
            }
        }
        $entityManager->flush();

        $this->dispatchBrowserEvent('manager:flush');
        $this->isEditing = false;

        $this->flashMessage = ' book updated';
    }
}

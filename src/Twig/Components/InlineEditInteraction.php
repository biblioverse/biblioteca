<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\Shelf;
use App\Entity\User;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent(method: 'get')]
class InlineEditInteraction extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;
    /**
     * @var list<Shelf>|Shelf[]
     */
    public $shelves;

    #[LiveProp(writable: true)]
    public ?BookInteraction $interaction = null;

    #[LiveProp(writable: true, format: 'Y-m-d')]
    public ?\DateTime $finished = null;

    #[LiveProp()]
    public User $user;
    #[LiveProp()]
    public Book $book;

    public ?string $flashMessage = null;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->interaction = $this->getInteraction();

        // @phpstan-ignore-next-line
        $this->finished = $this->interaction->getFinishedDate();

        $shelfRepository = $this->entityManager->getRepository(Shelf::class);

        $this->shelves = $shelfRepository->findBy(['user' => $this->user]);
        $this->shelves = array_filter($this->shelves, static fn ($item) => $item->getQueryString() === null);
    }

    private function getInteraction(): BookInteraction
    {
        $interaction = $this->interaction;
        if (!$interaction instanceof BookInteraction) {
            $bookInteractionRepo = $this->entityManager->getRepository(BookInteraction::class);
            $interaction = $bookInteractionRepo->findOneBy(['user' => $this->user, 'book' => $this->book]);
            if (null === $interaction) {
                $interaction = new BookInteraction();
                $interaction->setUser($this->user);
                $interaction->setBook($this->book);
                $interaction->setReadPages(0);
            }
        }

        return $interaction;
    }

    #[LiveAction]
    public function saveDate(): void
    {
        $interaction = $this->getInteraction();

        $interaction->setFinishedDate($this->finished);

        $this->entityManager->persist($interaction);

        $this->entityManager->flush();

        $this->flashMessage = 'Read status updated';
    }

    #[LiveAction]
    public function toggleReadStatus(#[LiveArg] string $value): void
    {
        $interaction = $this->getInteraction();

        $readStatus = ReadStatus::tryFrom($value);
        match ($readStatus) {
            null => $interaction->setReadStatus(ReadStatus::NotStarted),
            default => $interaction->setReadStatus($readStatus),
        };

        $this->book->setUpdated(new \DateTime('now'));
        $this->entityManager->persist($this->book);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessage = 'Read status updated';
    }

    #[LiveAction]
    public function toggleReadingList(#[LiveArg] string $value): void
    {
        $interaction = $this->getInteraction();

        $readingList = ReadingList::tryFrom($value);
        match ($readingList) {
            null => $interaction->setReadingList(ReadingList::NotDefined),
            default => $interaction->setReadingList($readingList),
        };

        $this->entityManager->persist($interaction);
        $this->book->setUpdated(new \DateTime('now'));
        $this->entityManager->persist($this->book);
        $this->entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessage = 'Reading list updated';
    }
}

<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\Shelf;
use App\Entity\User;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
use App\Form\InlineInteractionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
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

    /**
     * @var array<Shelf>
     */
    #[LiveProp()]
    public ?array $shelves = null;

    public ?string $flashMessage = null;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->interaction = $this->getOrCreateInteraction();

        // @phpstan-ignore-next-line
        $this->finished = $this->interaction->getFinishedDate();

        $shelfRepository = $this->entityManager->getRepository(Shelf::class);

        $this->shelves = $shelfRepository->findBy(['user' => $this->user]);
        $this->shelves = array_filter($this->shelves, static fn ($item) => $item->getQueryString() === null);
    }

    #[\Override]
    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->createNamed(uniqid('interactionform-', false), InlineInteractionType::class, $this->getOrCreateInteraction(), ['method' => 'POST']);
    }

    private function getOrCreateInteraction(): BookInteraction
    {
        $interaction = $this->interaction;
        if (!$interaction instanceof BookInteraction) {
            // as the interaction value should be passed from the parent component
            // if it is not set we consider no interaction exists yet
            $interaction = new BookInteraction();
            $interaction->setUser($this->user);
            $interaction->setBook($this->book);
            $interaction->setReadPages(0);
            $interaction->setFinishedDate(null);
        }

        return $interaction;
    }

    #[LiveAction]
    public function saveDate(): void
    {
        $interaction = $this->getOrCreateInteraction();

        $interaction->setFinished(!$interaction->isFinished());
        $this->book->setUpdated(new \DateTimeImmutable('now'));
        $this->entityManager->persist($this->book);
        $interaction->setFinishedDate($this->finished);

        $this->entityManager->persist($interaction);

        $this->entityManager->flush();

        $this->flashMessage = $interaction->isFinished()
            ? 'inlineeditinteraction.flash.read'
            : 'inlineeditinteraction.flash.unread';
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

        $this->book->setUpdated(new \DateTimeImmutable('now'));
        $this->entityManager->persist($this->book);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessage = 'inlineeditinteraction.flash.readstatus';
    }

    #[LiveAction]
    public function toggleReadingList(#[LiveArg] string $value): void
    {
        $interaction = $this->getOrCreateInteraction();

        $readingList = ReadingList::tryFrom($value);
        match ($readingList) {
            null => $interaction->setReadingList(ReadingList::NotDefined),
            default => $interaction->setReadingList($readingList),
        };

        $this->entityManager->persist($interaction);
        $this->book->setUpdated(new \DateTimeImmutable('now'));
        $this->entityManager->persist($this->book);
        $this->entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessage = 'inlineeditinteraction.flash.readinglist';
    }

    #[LiveAction]
    public function addToShelf(EntityManagerInterface $entityManager, #[LiveArg] int $shelf): void
    {
        $shelfRepository = $entityManager->getRepository(Shelf::class);

        $shelf = $shelfRepository->find($shelf);

        if (null === $shelf) {
            throw new \RuntimeException('Shelf not found');
        }

        $this->book->addShelf($shelf);

        $entityManager->flush();

        $this->flashMessage = 'inlineeditinteraction.flash.shelf';
    }

    #[LiveAction]
    public function removeFromShelf(EntityManagerInterface $entityManager, #[LiveArg] int $shelf): void
    {
        $shelfRepository = $entityManager->getRepository(Shelf::class);

        $shelf = $shelfRepository->find($shelf);

        if (null === $shelf) {
            throw new \RuntimeException('Shelf not found');
        }

        $this->book->removeShelf($shelf);

        $entityManager->flush();

        $this->flashMessage = 'inlineeditinteraction.flash.unshelf';
    }
}

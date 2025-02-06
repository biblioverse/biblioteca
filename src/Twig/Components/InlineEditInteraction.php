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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent(method: 'get')]
class InlineEditInteraction extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public ?BookInteraction $interaction = null;

    #[LiveProp(writable: true, format: 'Y-m-d')]
    public ?\DateTime $finished = null;

    #[LiveProp()]
    public Book $book;

    public ?string $flashMessage = null;
    public ?array $shelves = null;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly Security $security, private readonly FormFactoryInterface $formFactory)
    {
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('User not found');
        }

        return $user;
    }

    private function getUserShelves(): array
    {
        $shelves = $this->getCurrentUser()->getShelves()->toArray();

        return array_filter($shelves, static fn ($item) => $item->getQueryString() === null);
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->interaction = $this->getOrCreateInteraction();
        $this->shelves = $this->getUserShelves();
    }

    #[\Override]
    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->createNamed(uniqid('interactionform-', false), InlineInteractionType::class, $this->getOrCreateInteraction(), ['method' => 'POST']);
    }

    private function getOrCreateInteraction(): BookInteraction
    {
        $interaction = $this->entityManager->getRepository(BookInteraction::class)->findOneBy(['book' => $this->book, 'user' => $this->getCurrentUser()]);
        if (!$interaction instanceof BookInteraction) {
            $interaction = new BookInteraction();
            $interaction->setUser($this->getCurrentUser());
            $interaction->setBook($this->book);
            $interaction->setReadPages(0);
            $interaction->setFinishedDate(null);
        }

        $this->interaction = $interaction;

        return $interaction;
    }

    #[LiveAction]
    public function toggleReadStatus(#[LiveArg] string $value): void
    {
        $interaction = $this->getOrCreateInteraction();

        $readStatus = ReadStatus::tryFrom($value);
        match ($readStatus) {
            null => $interaction->setReadStatus(ReadStatus::NotStarted),
            default => $interaction->setReadStatus($readStatus),
        };

        if ($readStatus === ReadStatus::Finished) {
            $interaction->setFinishedDate(new \DateTimeImmutable());
        }

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

    #[LiveAction]
    public function saveInteraction(): void
    {
        $this->submitForm();

        $interaction = $this->getForm()->getData();

        if (!$interaction instanceof BookInteraction) {
            throw new \RuntimeException('Invalid data');
        }

        $this->entityManager->persist($interaction);
        $this->book->setUpdated(new \DateTimeImmutable('now'));
        $this->entityManager->persist($this->book);
        $this->entityManager->flush();
        $this->flashMessage = 'inlineeditinteraction.flash.saveInteraction';

        $this->dispatchBrowserEvent('manager:flush');
    }

    #[LiveAction]
    public function changeRating(#[LiveArg] int $value): void
    {
        $interaction = $this->getOrCreateInteraction();

        $interaction->setRating($value);

        $this->entityManager->persist($interaction);

        $this->book->setUpdated(new \DateTimeImmutable('now'));
        $this->entityManager->persist($this->book);
        $this->entityManager->flush();
        $this->interaction = $interaction;

        $this->dispatchBrowserEvent('manager:flush');
    }
}

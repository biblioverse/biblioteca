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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
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
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public ?BookInteraction $interaction = null;

    #[LiveProp()]
    public User $user;
    #[LiveProp()]
    public Book $book;

    public ?string $flashMessage = null;
    public ?string $flashMessageFav = null;
    /**
     * @var Shelf[]
     */
    public array $shelves = [];

    public function __construct(private EntityManagerInterface $entityManager, private FormFactoryInterface $formFactory)
    {

    }

    #[PostMount]
    public function postMount(): void
    {
        $this->interaction = $this->getInteraction();

        $shelfRepository = $this->entityManager->getRepository(Shelf::class);

        $this->shelves = $shelfRepository->findBy(['user' => $this->user]);
        $this->shelves = array_filter($this->shelves, static fn($item) => $item->getQueryString() === null);
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
    public function toggleReadStatus(): void
    {
        $interaction = $this->getInteraction();

        $interaction->setReadStatus(ReadStatus::toggle($interaction->getReadStatus()));

        $this->entityManager->persist($interaction);
        $this->entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessage = 'Read status updated';
    }

    #[LiveAction]
    public function toggleReadingList(EntityManagerInterface $entityManager): void
    {
        $interaction = $this->getInteraction();

        $interaction->setReadingList(ReadingList::toggle($interaction->getReadingList()));

        $entityManager->persist($interaction);
        $entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessageFav = 'Reading list updated';
    }
}

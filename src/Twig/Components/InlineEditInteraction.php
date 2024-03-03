<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\User;
use App\Form\InlineInteractionType;
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

#[AsLiveComponent(method: 'get')]
class InlineEditInteraction extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: ['finished', 'favorite'])]
    public ?BookInteraction $interaction = null;

    #[LiveProp()]
    public User $user;
    #[LiveProp()]
    public Book $book;

    public ?string $flashMessage = null;
    public ?string $flashMessageFav = null;

    public function __construct(private EntityManagerInterface $entityManager, private FormFactoryInterface $formFactory)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->createNamed(uniqid('interactionform-', false), InlineInteractionType::class, $this->getInteraction(), ['method' => 'POST']);
    }

    private function getInteraction(): BookInteraction
    {
        $interaction = $this->interaction;
        if (null === $interaction) {
            $bookInteractionRepo = $this->entityManager->getRepository(BookInteraction::class);
            $interaction = $bookInteractionRepo->findOneBy(['user' => $this->user, 'book' => $this->book]);
            if (null === $interaction) {
                $interaction = new BookInteraction();
                $interaction->setUser($this->user);
                $interaction->setBook($this->book);
                $interaction->setFinishedDate(new \DateTime('now'));
            }
        }

        return $interaction;
    }

    #[LiveAction]
    public function toggle(): void
    {
        $interaction = $this->getInteraction();

        $interaction->setFinished(!$interaction->isFinished());

        $this->entityManager->persist($interaction);
        $this->entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessage = 'Saved';
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
        $this->entityManager->flush();
        $this->flashMessageFav = 'Saved';
        $this->dispatchBrowserEvent('manager:flush');
    }

    #[LiveAction]
    public function toggleFavorite(EntityManagerInterface $entityManager): void
    {
        $interaction = $this->getInteraction();

        $interaction->setFavorite(!$interaction->isFavorite());

        $entityManager->persist($interaction);
        $entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessageFav = 'Saved';
    }
}

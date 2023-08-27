<?php
namespace App\Twig;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent()]
class InlineEditInteraction extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: ['finished','favorite'])]
    public ?BookInteraction $interaction=null;

    #[LiveProp()]
    public User $user;
    #[LiveProp()]
    public Book $book;

    public ?string $flashMessage = null;
    public ?string $flashMessageFav = null;


    private function getInteraction(EntityManagerInterface $entityManager):BookInteraction
    {
        $interaction = $this->interaction;
        if($interaction===null) {
            $bookInteractionRepo = $entityManager->getRepository(BookInteraction::class);
            $interaction = $bookInteractionRepo->findOneBy(['user' => $this->user, 'book' => $this->book]);
            if ($interaction === null) {
                $interaction = new BookInteraction();
                $interaction->setUser($this->user);
                $interaction->setBook($this->book);
            }
        }
        return $interaction;
    }

    #[LiveAction]
    public function toggle(EntityManagerInterface $entityManager):void
    {
        $interaction = $this->getInteraction($entityManager);

        $interaction->setFinished(!$interaction->isFinished());

        $entityManager->persist($interaction);
        $entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessage = 'Saved';
    }
    #[LiveAction]
    public function toggleFavorite(EntityManagerInterface $entityManager):void
    {
        $interaction = $this->getInteraction($entityManager);

        $interaction->setFavorite(!$interaction->isFavorite());

        $entityManager->persist($interaction);
        $entityManager->flush();
        $this->interaction = $interaction;

        $this->flashMessageFav = 'Saved';
    }
}

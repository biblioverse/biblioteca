<?php

namespace App\Twig\Components;

use App\Entity\Book;
use App\Enum\AgeCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(method: 'get')]
class InlineEditMultiple extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    /**
     * @var Book[]
     */
    #[LiveProp(updateFromParent: true)]
    public array $books;

    #[LiveProp(writable: true)]
    public array $fieldValue;

    #[LiveProp()]
    public bool $isEditing = false;

    #[LiveProp()]
    public string $field;

    public ?string $flashMessage = null;

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    /**
     * @throws \JsonException
     */
    #[LiveAction]
    #[LiveListener('submit')]
    public function save(EntityManagerInterface $entityManager): void
    {
        foreach ($this->books as $book) {
            switch ($this->field) {
                case 'authors':
                    $book->setAuthors($this->fieldValue);
                    break;
                case 'tags':
                    $book->setTags($this->fieldValue);
                    break;
                case 'serie':
                    $book->setSerie(implode(',', $this->fieldValue));
                    break;
                case 'ageCategory':
                    $value = reset($this->fieldValue);
                    $book->setAgeCategory(AgeCategory::tryFrom((int) $value));

                    break;
                default:
                    throw new \RuntimeException('Field not implemented for multiple edition');
            }
        }

        $entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');
        $this->isEditing = false;

        $this->flashMessage = 'books updated!';
    }
}

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
 * @phpstan-type AuthorsType array{ mainAuthor:string, authorSlug:string, bookCount:int, booksFinished:int }
 */
#[AsLiveComponent()]
class InlineEditAuthor extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;


    /**
     * @var AuthorsType
     */
    #[LiveProp(writable: ['mainAuthor'])]
    public array $author;

    /**
     * @var AuthorsType
     */
    #[LiveProp()]
    public array $originalAuthor;

    #[LiveProp()]
    public bool $isEditing = false;


    public ?string $flashMessage = null;

    #[LiveAction]
    public function activateEditing():void
    {
        $this->isEditing = true;
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager):void
    {
        $thisAuthor = $this->author;

        $bookRepo = $entityManager->getRepository(Book::class);

        $books = $bookRepo->findBy(['mainAuthor'=>$this->originalAuthor['mainAuthor']]);

        foreach($books as $book) {
            $book->setMainAuthor($this->author['mainAuthor']);
            $entityManager->persist($book);
        }

        $entityManager->flush();


        $authors = $bookRepo->getAllAuthors();

        $author = array_filter($authors, static function($s) use ($thisAuthor) {
            return $s['mainAuthor'] === $thisAuthor['mainAuthor'];
        } );

        $author= current($author);

        if($author === false) {
            throw new \RuntimeException('Author not found');
        }

        $this->originalAuthor = $author;
        $this->author = $author;
        $this->isEditing = false;



        $this->flashMessage = count($books).' books updated';
    }
}

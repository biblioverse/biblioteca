<?php
namespace App\Twig;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

/**
 * @phpstan-type SeriesType array{ serie:string, serieSlug:string, bookCount:int, booksFinished:int }
 */
#[AsLiveComponent()]
class InlineEditSerie extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;


    /**
     * @var SeriesType
     */
    #[LiveProp(writable: ['serie'])]
    public array $serie;

    /**
     * @var SeriesType
     */
    #[LiveProp()]
    public array $originalSerie;

    #[LiveProp()]
    public bool $isEditing = false;


    public ?string $flashMessage = null;

    #[LiveAction]
    public function activateEditing() :void
    {
        $this->isEditing = true;
    }

    /**
     * @throws RuntimeException
     */
    #[LiveAction]
    public function save(EntityManagerInterface $entityManager):void
    {
        $thisSerie = $this->serie;

        $bookRepo = $entityManager->getRepository(Book::class);

        $books = $bookRepo->findBy(['serie'=>$this->originalSerie['serie']]);

        foreach($books as $book) {
            $book->setSerie($this->serie['serie']);
            $entityManager->persist($book);
        }

        $entityManager->flush();


        $series = $bookRepo->getAllSeries();

        $serie = array_filter($series, static function($s) use ($thisSerie) {
            return $s['serie'] === $thisSerie['serie'];
        } );


        $serie= current($serie);
        if ($serie === false) {
            throw new RuntimeException('Serie not found');
        }

        $this->originalSerie = $serie;
        $this->serie = $serie;
        $this->isEditing = false;



        $this->flashMessage = count($books).' books updated';
    }
}

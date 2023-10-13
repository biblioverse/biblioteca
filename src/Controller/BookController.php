<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\BookFileSystemManager;
use App\Service\BookSuggestions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/{book}/{slug}', name: 'app_book')]
    public function index(Request $request, Book $book, string $slug, BookSuggestions $bookSuggestions, BookRepository $bookRepository): Response
    {
        if ($slug !== $book->getSlug()) {
            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        $suggestions = BookSuggestions::EMPTY_SUGGESTIONS;
        $google = (bool) $request->get('google', false);
        $openLibraries = (bool) $request->get('openlib', false);
        if (!$book->isVerified() && $google === true) {
            $suggestions = $bookSuggestions->getGoogleSuggestions($book);
        } elseif (!$book->isVerified() && $openLibraries === true) {
            $suggestions = $bookSuggestions->getCategorySuggestions($book);
        }

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_book_delete', [
                'id' => $book->getId(),
            ]))
            ->setMethod('POST')
            ->add('delete', SubmitType::class, [
                'label' => 'Delete',
                'attr' => [
                    'class' => 'btn btn-danger',
                ],
            ])
            ->getForm();

        $serie = [];
        $serieMax = 0;
        if ($book->getSerie() !== null) {
            $booksInSerie = $bookRepository->findBy(['serie' => $book->getSerie()], ['serieIndex' => 'ASC']);
            foreach ($booksInSerie as $bookInSerie) {
                $index = $bookInSerie->getSerieIndex();
                if ($index === 0.0 || floor($index ?? 0.0) !== $index) {
                    $index = '?';
                }
                $serie[$index] = $serie[$index] ?? [];
                $serie[$index][] = $bookInSerie;
            }
            $keys = array_filter(array_keys($serie), static fn ($key) => is_numeric($key));

            if (count($keys) > 0) {
                $serieMax = max($keys);
            }
        }

        $sameAuthorBooks = $bookRepository->getWithSameAuthors($book, 6);

        return $this->render('book/index.html.twig', [
            'book' => $book,
            'serie' => $serie,
            'serieMax' => $serieMax,
            'sameAuthor' => $sameAuthorBooks,
            'suggestions' => $suggestions,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/download-image/{id}/{image}', name: 'app_book_downloadImage')]
    public function downloadImage(Book $book, string $image, BookSuggestions $bookSuggestions, EntityManagerInterface $entityManager, BookFileSystemManager $fileSystemManager): Response
    {
        $suggestions = $bookSuggestions->getGoogleSuggestions($book);

        $url = $suggestions['image'][$image] ?? null;

        if ($url === null) {
            throw $this->createNotFoundException('Image not found');
        }

        $book = $fileSystemManager->downloadBookCover($book, $url);

        $entityManager->flush();

        return $this->redirectToRoute('app_book', [
            'book' => $book->getId(),
            'slug' => $book->getSlug(),
        ], 301);
    }

    #[Route('/extract-cover/{id}/fromFile', name: 'app_extractCover')]
    public function extractCover(Book $book, BookSuggestions $bookSuggestions, EntityManagerInterface $entityManager, BookFileSystemManager $fileSystemManager): Response
    {
        $book = $fileSystemManager->extractCover($book);

        $entityManager->flush();

        return $this->redirectToRoute('app_book', [
            'book' => $book->getId(),
            'slug' => $book->getSlug(),
        ], 301);
    }

    #[Route('/delete/{id}/now', name: 'app_book_delete', methods: ['POST'])]
    public function deleteBook(int $id, EntityManagerInterface $entityManager, BookFileSystemManager $fileSystemManager): Response
    {
        /** @var Book $book */
        $book = $entityManager->getRepository(Book::class)->find($id);

        $fileSystemManager->deleteBookFiles($book);

        $entityManager->remove($book);

        $entityManager->flush();

        $this->addFlash('success', 'Book deleted');

        return $this->redirectToRoute('app_homepage');
    }
}

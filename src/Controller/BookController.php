<?php

namespace App\Controller;

use App\Entity\Book;
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
    public function index(Request $request, Book $book, string $slug, BookSuggestions $bookSuggestions): Response
    {
        if ($slug !== $book->getSlug()) {
            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        $suggestions = BookSuggestions::EMPTY_SUGGESTIONS;
        $forceSuggestions = (bool) $request->get('refresh', false);
        if (!$book->isVerified() && $forceSuggestions === true) {
            $suggestions = $bookSuggestions->getSuggestions($book);
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

        return $this->render('book/index.html.twig', [
            'book' => $book,
            'suggestions' => $suggestions,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/download-image/{id}/{image}', name: 'app_book_downloadImage')]
    public function downloadImage(Book $book, string $image, BookSuggestions $bookSuggestions, EntityManagerInterface $entityManager, BookFileSystemManager $fileSystemManager): Response
    {
        $suggestions = $bookSuggestions->getSuggestions($book);

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

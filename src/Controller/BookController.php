<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\User;
use App\Repository\BookInteractionRepository;
use App\Repository\BookRepository;
use App\Service\BookFileSystemManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/{book}/{slug}', name: 'app_book')]
    public function index(Request $request, Book $book, string $slug, BookRepository $bookRepository, EntityManagerInterface $manager, BookFileSystemManager $fileSystemManager): Response
    {
        if ($slug !== $book->getSlug()) {
            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
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

        $calculatedPath = $fileSystemManager->getCalculatedFilePath($book, false).$fileSystemManager->getCalculatedFileName($book);
        $needsRelocation = $fileSystemManager->getCalculatedFilePath($book, false) !== $book->getBookPath();

        $interaction = $manager->getRepository(BookInteraction::class)->findOneBy([
            'book' => $book,
            'user' => $this->getUser(),
        ]);

        return $this->render('book/index.html.twig', [
            'book' => $book,
            'serie' => $serie,
            'serieMax' => $serieMax,
            'sameAuthor' => $sameAuthorBooks,
            'interaction' => $interaction,
            'form' => $form->createView(),
            'calculatedPath' => $calculatedPath,
            'needsRelocation' => $needsRelocation,
        ]);
    }

    #[Route('/{book}/{slug}/read', name: 'app_book_read')]
    public function read(Request $request, Book $book, string $slug, BookFileSystemManager $fileSystemManager, PaginatorInterface $paginator, EntityManagerInterface $manager): Response
    {
        set_time_limit(120);
        if ($slug !== $book->getSlug()) {
            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('danger', 'You need to be logged in to read books');
            throw $this->createAccessDeniedException();
        }

        switch ($book->getExtension()) {
            // case 'epub':
            //    break;
            case 'pdf':
            case 'cbr':
            case 'cbz':
                $files = $fileSystemManager->extractFilesToRead($book);
                break;
            default:
                $this->addFlash('danger', 'Unsupported book format');

                return $this->redirectToRoute('app_book', [
                    'book' => $book->getId(),
                    'slug' => $book->getSlug(),
                ]);
        }

        if ($book->getPageNumber() !== count($files)) {
            $book->setPageNumber(count($files));
            $manager->flush();
        }
        $interaction = $manager->getRepository(BookInteraction::class)->findOneBy([
            'book' => $book,
            'user' => $this->getUser(),
        ]);
        if ($interaction === null) {
            $interaction = new BookInteraction();
            $interaction->setBook($book);
            $interaction->setUser($user);
        }

        // @phpstan-ignore-next-line
        $page = (int) $request->get('page', $interaction->getReadPages() ?? 1);

        $page = max(1, $page);

        if (!$interaction->isFinished() && $interaction->getReadPages() < $page) {
            $interaction->setReadPages($page);
        }
        $manager->persist($interaction);
        $manager->flush();

        if (!$interaction->isFinished() && $page === $book->getPageNumber()) {
            $interaction->setFinished(true);
            $interaction->setFinishedDate(new \DateTime());
            $this->addFlash('success', 'Book finished! Congratulations!');
            $manager->flush();

            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ]);
        }

        $pagination = $paginator->paginate(
            $files,
            $page,
            1
        );

        // @phpstan-ignore-next-line
        $pagination->setTemplate('book/_knp_minimal_pagination.html.twig');

        return $this->render('book/reader-files.html.twig', [
            'book' => $book,
            'page' => $page,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/extract-cover/{id}/fromFile', name: 'app_extractCover')]
    public function extractCover(Book $book, EntityManagerInterface $entityManager, BookFileSystemManager $fileSystemManager): Response
    {
        $book = $fileSystemManager->extractCover($book);

        $entityManager->flush();

        return $this->redirectToRoute('app_book', [
            'book' => $book->getId(),
            'slug' => $book->getSlug(),
        ], 301);
    }

    #[Route('/started', name: 'app_started')]
    public function started(BookInteractionRepository $repository): Response
    {
        $books = $repository->getStartedBooks();

        return $this->render('book/started.html.twig', [
            'books' => $books,
        ]);
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

        return $this->redirectToRoute('app_allbooks');
    }

    #[Route('/new/consume/upload', name: 'app_book_upload_consume')]
    public function upload(Request $request, BookFileSystemManager $fileSystemManager): Response
    {
        $form = $this->createFormBuilder()
            ->setMethod('POST')
            ->add('file', FileType::class, [
                'label' => 'Book',
                'required' => false,
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Upload',
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->getForm()
        ;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<int, UploadedFile> $files */
            $files = (array) $form->get('file')->getData();
            if (count($files) > 0) {
                $fileSystemManager->uploadFilesToConsumeDirectory($files);

                return $this->redirectToRoute('app_book_consume');
            }
        }

        return $this->render('book/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new/consume/files', name: 'app_book_consume')]
    public function consume(Request $request, BookFileSystemManager $fileSystemManager): Response
    {
        $bookFiles = $fileSystemManager->getAllBooksFiles(true);

        $bookFiles = iterator_to_array($bookFiles);

        $consume = $request->get('consume');
        if ($consume !== null) {
            set_time_limit(240);
            foreach ($bookFiles as $bookFile) {
                if ($bookFile->getRealPath() !== $consume) {
                    continue;
                }
                $childProcess = new Process(['/var/www/html/bin/console', 'books:scan', '--book-path', $bookFile->getRealPath()]);

                $childProcess->start();

                $childProcess->wait();

                $this->addFlash('success', 'Book '.$bookFile->getFilename().' consumed');

                return $this->redirectToRoute('app_book_consume');
            }
        }

        $delete = $request->get('delete');
        if ($delete !== null) {
            foreach ($bookFiles as $bookFile) {
                if ($bookFile->getRealPath() !== $delete) {
                    continue;
                }
                unlink($bookFile->getRealPath());
                $this->addFlash('success', 'Book '.$bookFile->getFilename().' deleted');

                return $this->redirectToRoute('app_book_consume');
            }
        }

        return $this->render('book/consume.html.twig', [
            'books' => $bookFiles,
        ]);
    }

    #[Route('/relocate/{id}/files', name: 'app_book_relocate')]
    public function relocate(Book $book, BookFileSystemManager $fileSystemManager, EntityManagerInterface $entityManager): Response
    {
        $book = $fileSystemManager->renameFiles($book);
        $entityManager->persist($book);
        $entityManager->flush();

        return $this->redirectToRoute('app_book', [
            'book' => $book->getId(),
            'slug' => $book->getSlug(),
        ]);
    }
}

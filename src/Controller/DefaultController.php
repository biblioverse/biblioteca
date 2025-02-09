<?php

namespace App\Controller;

use App\Enum\ReadStatus;
use App\Repository\BookInteractionRepository;
use App\Repository\BookRepository;
use App\Service\BookFileSystemManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(BookRepository $bookRepository, BookInteractionRepository $bookInteractionRepository): Response
    {
        $exts = $bookRepository->countBooks(false);
        $types = $bookRepository->countBooks(true);

        $reading = $bookInteractionRepository->getStartedBooks();
        $readList = $bookInteractionRepository->getFavourite(6);

        $startedSeries = $bookRepository->getStartedSeries()->getResult();
        $booksInSeries = [];
        foreach ($startedSeries as $serie) {
            if ($serie['booksFinished'] === $serie['bookCount']) {
                continue;
            }

            $booksInSeries[$serie['item']] = [
                'progress' => $serie,
                'book' => $bookRepository->getFirstUnreadBook($serie['item']),
            ];
        }

        $tags = $bookRepository->getAllTags();

        $keys = $tags === [] ? [] : array_rand($tags, min(count($tags), 4));

        if (!is_array($keys)) {
            $keys = [];
        }

        $inspiration = [];
        foreach ($keys as $key) {
            $randomBooks = [];

            $inspiration[] = [
                ...$tags[$key],
                'books' => $randomBooks,
            ];
        }

        return $this->render('default/dashboard.html.twig', [
            'extensions' => $exts,
            'types' => $types,
            'reading' => $reading,
            'readlist' => $readList,
            'booksInSeries' => $booksInSeries,
            'inspiration' => $inspiration,
        ]);
    }

    #[Route('/reading-list', name: 'app_readinglist')]
    public function readingList(BookInteractionRepository $bookInteractionRepository): Response
    {
        $readList = $bookInteractionRepository->getFavourite(hideFinished: false);

        $statuses = [
            'unread' => [],
            'finished' => [],
        ];
        foreach ($readList as $bookInteraction) {
            if ($bookInteraction->getReadStatus() === ReadStatus::Finished) {
                $statuses['finished'][] = $bookInteraction->getBook();
            } else {
                $statuses['unread'][] = $bookInteraction->getBook();
            }
        }

        return $this->render('default/readingList.html.twig', [
            'readlist' => $statuses,
        ]);
    }

    #[Route('/all', name: 'app_allbooks')]
    public function allbooks(): Response
    {
        return $this->render('default/index.html.twig');
    }

    #[Route('/timeline/{type?}/{year?}', name: 'app_timeline', requirements: ['page' => '\d+'])]
    public function timeline(?string $type, ?string $year, BookRepository $bookRepository): Response
    {
        $redirectType = $type ?? 'all';
        $redirectYear = $year ?? date('Y');
        if ($redirectYear !== $year || $redirectType !== $type) {
            return $this->redirectToRoute('app_timeline', ['type' => $redirectType, 'year' => $redirectYear]);
        }

        if ($year === 'null') {
            $year = null;
        }

        $qb = $bookRepository->getReadBooks($year, $type);

        $types = $bookRepository->getReadTypes();
        $years = $bookRepository->getReadYears();

        $books = $qb->getQuery()->getResult();

        return $this->render('default/timeline.html.twig', [
            'books' => $books,
            'year' => $year,
            'type' => $type,
            'types' => $types,
            'years' => $years,
        ]);
    }

    #[Route('/not-verified', name: 'app_notverified')]
    public function notverified(Request $request, BookRepository $bookRepository, BookFileSystemManagerInterface $bookFileSystemManager, EntityManagerInterface $entityManager): Response
    {
        $books = $bookRepository->findBy(['verified' => false], ['serieIndex' => 'asc'], 100);

        $action = $request->get('action');
        if ($action !== null) {
            switch ($action) {
                case 'relocate':
                    if ($this->isGranted('RELOCATE')) {
                        $success = true;
                        foreach ($books as $book) {
                            try {
                                $book = $bookFileSystemManager->renameFiles($book);
                                $entityManager->persist($book);
                            } catch (\Exception $e) {
                                $success = false;
                                $this->addFlash('danger', 'Error while relocating files: '.$e->getMessage());
                            }
                        }
                        $entityManager->flush();
                        if ($success) {
                            $this->addFlash('success', 'Files relocated');
                        }
                    } else {
                        $this->addFlash('danger', 'You do not have the permission to relocate files');
                    }

                    return $this->redirectToRoute('app_notverified');
                case 'extract':
                    $success = true;

                    foreach ($books as $book) {
                        try {
                            $book = $bookFileSystemManager->extractCover($book);
                            $entityManager->persist($book);
                        } catch (\Exception $e) {
                            $success = false;
                            $this->addFlash('danger', $e->getMessage());
                        }
                    }
                    $entityManager->flush();
                    if ($success) {
                        $this->addFlash('success', 'Covers extracted');
                    }

                    return $this->redirectToRoute('app_notverified');
                case 'validate':
                    try {
                        foreach ($books as $book) {
                            $book->setVerified(true);
                            $entityManager->persist($book);
                        }
                        $entityManager->flush();
                        $this->addFlash('success', 'Books validated');
                    } catch (\Exception $e) {
                        $this->addFlash('error', $e->getMessage());
                    }

                    return $this->redirectToRoute('app_notverified');
                default:
                    throw new \Exception('Invalid action');
            }
        }

        return $this->render('default/notverified.html.twig', [
            'books' => $books,
        ]);
    }
}

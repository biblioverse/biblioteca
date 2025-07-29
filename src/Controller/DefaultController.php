<?php

namespace App\Controller;

use App\Enum\ReadStatus;
use App\Repository\BookInteractionRepository;
use App\Repository\BookRepository;
use App\Service\BookFileSystemManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
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

        /**
         * @var array<string, array{booksFinished: int, bookCount: int, item: string}> $startedSeries
         */
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
        // Check if sort parameters are provided in the request
        $sortBy = $request->get('sort');
        $sortOrder = $request->get('order');

        // If no sort parameters in request, try to get from cookies
        if ($sortBy === null) {
            $sortBy = $request->cookies->get('notverified_sort', 'path');
        }
        if ($sortOrder === null) {
            $sortOrder = $request->cookies->get('notverified_order', 'asc');
        }

        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        // Validate sort by
        if (!in_array($sortBy, ['title', 'serie', 'path'], true)) {
            $sortBy = 'path';
        }
        $orderBy = match ($sortBy) {
            'title' => ['title' => $sortOrder],
            'serie' => ['serie' => $sortOrder, 'serieIndex' => $sortOrder],
            default => ['bookPath' => $sortOrder, 'bookFilename' => $sortOrder],
        };

        $books = $bookRepository->findBy(['verified' => false], $orderBy, 100);

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

        $response = $this->render('default/notverified.html.twig', [
            'books' => $books,
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder,
        ]);

        // Set cookies if sort parameters were provided in the request
        if ($request->get('sort') !== null || $request->get('order') !== null) {
            $farAway = strtotime('+1 year');

            $farAway = (string) $farAway;
            $response->headers->setCookie(
                new Cookie('notverified_sort', $sortBy, $farAway, '/', null, false, false)
            );
            $response->headers->setCookie(
                new Cookie('notverified_order', $sortOrder, $farAway, '/', null, false, false)
            );
        }

        return $response;
    }
}

<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Andante\PageFilterFormBundle\PageFilterFormTrait;
use App\Form\BookFilterType;
use App\Repository\BookInteractionRepository;
use App\Repository\BookRepository;
use App\Service\FilteredBookUrlGenerator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    use PageFilterFormTrait;

    #[Route('/', name: 'app_dashboard')]
    public function index(BookRepository $bookRepository, BookInteractionRepository $bookInteractionRepository): Response
    {
        $exts = $bookRepository->countBooks(false);
        $types = $bookRepository->countBooks(true);

        $books = $bookInteractionRepository->getStartedBooks();
        $readList = $bookInteractionRepository->getFavourite();

        $series = $bookRepository->getStartedSeries()->getResult();

        $tags = $bookRepository->getAllTags();

        $keys = array_rand($tags, 4);

        $inspiration = [];
        foreach ($keys as $key) {
            $randomBooks = $bookRepository->findByTag($tags[$key]['item'], 6);
            $inspiration[] = [
                ...$tags[$key],
                'books' => $randomBooks,
            ];
        }

        return $this->render('default/dashboard.html.twig', [
            'extensions' => $exts,
            'types' => $types,
            'books' => $books,
            'readlist' => $readList,
            'series' => $series,
            'inspiration' => $inspiration,
        ]);
    }

    #[Route('/all/{page}', name: 'app_allbooks', requirements: ['page' => '\d+'])]
    public function allbooks(Request $request, BookRepository $bookRepository, FilteredBookUrlGenerator $filteredBookUrlGenerator, PaginatorInterface $paginator, int $page = 1): Response
    {
        $qb = $bookRepository->getAllBooksQueryBuilder();

        $form = $this->createAndHandleFilter(BookFilterType::class, $qb, $request);

        if ($request->getQueryString() === null) {
            $modifiedParams = $filteredBookUrlGenerator->getParametersArrayForCurrent();

            return $this->redirectToRoute('app_allbooks', ['page' => 1, ...$modifiedParams]);
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $page,
            18
        );

        if ($page > ($pagination->getTotalItemCount() / 18) + 1) {
            return $this->redirectToRoute('app_allbooks', ['page' => 1, ...$request->query->all()]);
        }

        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/timeline/{type?}/{year?}', name: 'app_timeline', requirements: ['page' => '\d+'])]
    public function timeline(?string $type, ?string $year, BookRepository $bookRepository, FilteredBookUrlGenerator $filteredBookUrlGenerator, PaginatorInterface $paginator, int $page = 1): Response
    {
        $redirectType = $type === null ? 'all' : $type;
        $redirectYear = $year === null ? date('Y') : $year;
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
}

<?php

namespace App\Controller;

use Andante\PageFilterFormBundle\PageFilterFormTrait;
use App\Form\BookFilterType;
use App\Repository\BookRepository;
use App\Service\FilteredBookUrlGenerator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    use PageFilterFormTrait;

    #[Route('/{page}', name: 'app_homepage', requirements: ['page' => '\d+'])]
    public function index(Request $request, BookRepository $bookRepository, FilteredBookUrlGenerator $filteredBookUrlGenerator, PaginatorInterface $paginator, int $page = 1): Response
    {
        $qb = $bookRepository->getAllBooksQueryBuilder();

        $form = $this->createAndHandleFilter(BookFilterType::class, $qb, $request);

        if ($request->getQueryString() === null) {
            $modifiedParams = $filteredBookUrlGenerator->getParametersArrayForCurrent();

            return $this->redirectToRoute('app_homepage', ['page' => 1, ...$modifiedParams]);
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $page,
            18
        );

        if ($page > ($pagination->getTotalItemCount() / 18) + 1) {
            return $this->redirectToRoute('app_homepage', ['page' => 1, ...$request->query->all()]);
        }

        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/timeline/{type?}/{year?}', name: 'app_timeline', requirements: ['page' => '\d+'])]
    public function timeline(?string $type, ?string $year, Request $request, BookRepository $bookRepository, FilteredBookUrlGenerator $filteredBookUrlGenerator, PaginatorInterface $paginator, int $page = 1): Response
    {
        if ($type === null) {
            $redirectType = 'all';
        } else {
            $redirectType = $type;
        }
        if ($year === null) {
            $redirectYear = date('Y');
        } else {
            $redirectYear = $year;
        }
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

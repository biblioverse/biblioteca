<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/series')]
class SerieController extends AbstractController
{
    #[Route('/', name: 'app_series')]
    public function index(BookRepository $bookRepository): Response
    {
        $series = $bookRepository->getAllSeries();

        return $this->render('serie/index.html.twig', [
            'series' => $series,
        ]);
    }

    #[Route('/{slug}/{page}', name: 'app_series_detail', requirements: ['page' => '\d+'])]
    public function detail(string $slug, BookRepository $bookRepository, PaginatorInterface $paginator, int $page=1): Response
    {
        $series = $bookRepository->getAllSeries();
        $serie = array_filter($series, fn($serie) => $serie['serieSlug'] === $slug);

        $serie= current($serie);

        $pagination = $paginator->paginate(
            $bookRepository->getBySerieQuery($slug),
            $page,
            18
        );
        /** @var Book $firstBook */
        $firstBook = current($pagination->getItems());
        return $this->render('serie/detail.html.twig', [
            'pagination' => $pagination,
            'serie' => $serie,
        ]);
    }
}

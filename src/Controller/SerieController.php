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
    #[Route('/{page}', name: 'app_serie', requirements: ['page' => '\d+'])]
    public function index(BookRepository $bookRepository, PaginatorInterface $paginator, int $page = 1): Response
    {
        $series = $bookRepository->getAllSeries()->getResult();

        $pagination = $paginator->paginate($series, $page, 18);

        return $this->render('group/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'type' => 'serie',
        ]);
    }

    #[Route('/{slug}/{page}', name: 'app_serie_detail', requirements: ['page' => '\d+'])]
    public function detail(string $slug, BookRepository $bookRepository, PaginatorInterface $paginator, int $page = 1): Response
    {
        $series = $bookRepository->getAllSeries()->getResult();
        if (!is_array($series)) {
            throw $this->createNotFoundException('No series found');
        }

        $serie = array_filter($series, static fn ($serie) => $serie['slug'] === $slug);

        $serie = current($serie);

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

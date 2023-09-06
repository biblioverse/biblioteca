<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tags')]
class TagController extends AbstractController
{
    #[Route('/{page}', name: 'app_tags', requirements: ['page' => '\d+'])]
    public function index(BookRepository $bookRepository, PaginatorInterface $paginator, int $page = 1): Response
    {
        $tags = $bookRepository->getAllTags();

        $pagination = $paginator->paginate($tags, $page, 18);

        return $this->render('group/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'type' => 'tags',
        ]);
    }

    #[Route('/detail/{slug}/{page}', name: 'app_tags_detail', requirements: ['page' => '\d+'])]
    public function detail(string $slug, BookRepository $bookRepository, PaginatorInterface $paginator, int $page = 1): Response
    {
        $tags = $bookRepository->getAllTags();

        $slug = urldecode($slug);

        $tag = $tags[$slug] ?? null;

        if (null === $tag) {
            return $this->redirectToRoute('app_tags');
        }

        $pagination = $paginator->paginate(
            $bookRepository->getByTagQuery($tag['item']),
            $page,
            18
        );

        return $this->render('author/detail.html.twig', [
            'pagination' => $pagination,
            'author' => $tag,
        ]);
    }
}

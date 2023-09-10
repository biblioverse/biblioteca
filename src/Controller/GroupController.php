<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/groups')]
class GroupController extends AbstractController
{
    public function __construct(private BookRepository $bookRepository, private PaginatorInterface $paginator)
    {
    }

    #[Route('/{type}/{page}', name: 'app_groups', requirements: ['page' => '\d+'])]
    public function groups(Request $request, string $type, int $page = 1): Response
    {
        $group = [];
        switch ($type) {
            case 'authors':
                $group = $this->bookRepository->getAllAuthors();
                break;
            case 'tags':
                $group = $this->bookRepository->getAllTags();
                break;
            case 'publisher':
                $group = $this->bookRepository->getAllPublishers()->getResult();
                break;
            case 'serie':
                $group = $this->bookRepository->getAllSeries()->getResult();
                break;
        }

        $search = $request->get('search', '');

        if ($search !== '') {
            $group = array_filter($group, static function ($item) use ($search) {
                return str_contains(strtolower($item['item']), strtolower($search));
            });
        }

        $pagination = $this->paginator->paginate($group, $page, 300);

        return $this->render('group/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'type' => $type,
            'search' => $search,
        ]);
    }
}

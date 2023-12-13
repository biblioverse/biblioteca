<?php

namespace App\Controller;

use App\Repository\BookRepository;
use http\Exception\RuntimeException;
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
        $incomplete = $request->get('incomplete', 0);
        if (!is_numeric($incomplete)) {
            throw new RuntimeException('Invalid incomplete type');
        }
        $incomplete = (int) $incomplete;
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
                if ($incomplete === 1) {
                    $group = $this->bookRepository->getIncompleteSeries()->getResult();
                    break;
                }
                $group = $this->bookRepository->getAllSeries()->getResult();
                break;
        }
        $search = $request->get('search', '');

        if (!is_string($search)) {
            throw new RuntimeException('Invalid search type');
        }
        if (!is_array($group)) {
            throw new RuntimeException('Invalid group type');
        }

        if ($search !== '') {
            $group = array_filter($group, static function ($item) use ($search) {
                return str_contains(strtolower($item['item']), strtolower($search));
            });
        }
        $pagination = $this->paginator->paginate($group, $page, 150);

        return $this->render('group/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'type' => $type,
            'incomplete' => $incomplete,
            'search' => $search,
        ]);
    }

    public function firstBook(array $item, string $type, BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findBy([$type => $item['item']], ['serieIndex' => 'ASC'],);

        return $this->render('group/_teaser.html.twig', ['books' => $books]);
    }
}

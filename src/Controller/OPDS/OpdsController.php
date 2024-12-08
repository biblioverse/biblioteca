<?php

namespace App\Controller\OPDS;

use App\OPDS\Opds;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/opds/{accessKey}', name: 'opds_')]
class OpdsController extends AbstractController
{
    public function __construct(private Opds $opds, private BookRepository $bookRepository)
    {
    }

    #[Route('/', name: 'start')]
    public function index(Request $request): Response
    {
        $this->opds->setCurrentAccessKey($request->get('accessKey'));
        return $this->opds->getStartPage();
    }

    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $this->opds->setCurrentAccessKey($request->get('accessKey'));
        return $this->opds->getSearchPage($request->get('q', $request->get('query', '')), $request->get('page', 1));
    }

    #[Route('/group/{type}', name: 'group')]
    public function group(Request $request, string $type): Response
    {
        switch ($type) {
            case 'authors':
                $group = $this->bookRepository->getAllAuthors();
                break;
            case 'tags':
                $group = $this->bookRepository->getAllTags();
                break;
            case 'serie':
                $group = $this->bookRepository->getAllSeries()->getResult();
                break;
            default:
                return $this->createAccessDeniedException('Invalid group type');
        }
        $this->opds->setCurrentAccessKey($request->get('accessKey'));
        return $this->opds->getGroupPage($type, $group);
    }

    #[Route('/group/{type}/{item}', name: 'group_item', requirements: ['item' => '.+'])]
    public function groupItem(Request $request, string $type, string $item): Response
    {
        switch ($type) {
            case 'authors':
                $group = $this->bookRepository->findByAuthor($item);
                break;
            case 'tags':
                $group = $this->bookRepository->findByTag($item);
                break;
            case 'serie':
                $group = $this->bookRepository->findBy(['serie' => $item]);
                break;
        }

        $this->opds->setCurrentAccessKey($request->get('accessKey'));
        return $this->opds->getGroupItemPage($type, $item, $group);
    }

}

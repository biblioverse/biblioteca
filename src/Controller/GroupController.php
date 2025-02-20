<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/groups')]
class GroupController extends AbstractController
{
    public function __construct(private readonly BookRepository $bookRepository)
    {
    }

    #[Route('/{type}/{letter}/{page}', name: 'app_groups', defaults: ['page' => 1], requirements: ['page' => '\d+', 'letter' => '[a-zA-Z0-9]|alpha|$^'])]
    public function groups(Request $request, string $type, int $page, ?string $letter = null): Response
    {
        $group = [];
        $count = 0;
        $letter = trim((string) $letter) === '' ? null : strtolower((string) $letter);
        $page = $letter === null ? $page : null; // Paginate except if filtered
        $perPage = 50;

        switch ($type) {
            case 'authors':
                $count = $this->bookRepository->getAllAuthorsCount();
                $group = $this->bookRepository->getAllAuthors($page, $perPage);
                break;
            case 'tags':
                $count = $this->bookRepository->getAllTagsCount();
                $group = $this->bookRepository->getAllTags($page, $perPage);
                break;
            case 'publisher':
                $count = $this->bookRepository->getAllPublishersCount();
                $group = $this->bookRepository->getAllPublishers($page, $perPage);
                break;
            case 'serie':
                $count = $this->bookRepository->getAllSeriesCount();
                $group = $this->bookRepository->getAllSeries($page, $perPage);
                break;
        }
        $search = $request->query->getString('search');
        if (trim($search) !== '') {
            $page = null; // Don't paginate search results
        }

        $group = match ($search) {
            default => array_filter($group, static fn (mixed $item) => str_contains(strtolower($item['item']), strtolower($search))),
            '' => array_filter(
                $group,
                static fn (mixed $item) => $letter === null
                || ($letter !== 'alpha' && str_starts_with(strtolower($item['item']), $letter))
                || ($letter === 'alpha' && preg_match('/^\d/', $item['item']) === 1)
            ),
        };

        return $this->render('group/index.html.twig', [
            'count' => $count,
            'page' => $page,
            'perPage' => $perPage,
            'total' => intval(ceil($count / $perPage)),
            'group' => $group,
            'letter' => $letter,
            'type' => $type,
            'search' => $search,
        ]);
    }
}

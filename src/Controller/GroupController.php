<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/groups')]
/**
 * @phpstan-import-type GroupType from BookRepository
 */
class GroupController extends AbstractController
{
    public function __construct(private readonly BookRepository $bookRepository)
    {
    }

    #[Route('/{type}/{letter}', name: 'app_groups')]
    public function groups(Request $request, string $type, string $letter = 'a'): Response
    {
        $group = [];
        $letter = strtolower($letter);
        if (strlen($letter) !== 1 || !ctype_alpha($letter)) {
            return $this->redirectToRoute('app_groups', ['type' => $type, 'letter' => 'a']);
        }

        switch ($type) {
            case 'authors':
                $group = $this->bookRepository->getAllAuthors();
                break;
            case 'tags':
                $group = $this->bookRepository->getAllTags();
                break;
            case 'publisher':
                $group = $this->bookRepository->getAllPublishers();
                break;
            case 'serie':
                $group = $this->bookRepository->getAllSeries();
                break;
        }
        $search = $request->request->getString('search', $request->query->getString('search'));
        $group = match ($search) {
            default => array_filter(
                $group,
                /* @var GroupType $item */
                static fn (array $item): bool => str_contains(strtolower((string) $item['item']), strtolower($search))
            ),
            '' => array_filter(
                $group,
                /* @var GroupType $item */
                static fn (array $item): bool => str_starts_with(strtolower((string) $item['item']), $letter)
            ),
        };

        return $this->render('group/index.html.twig', [
            'group' => $group,
            'letter' => $letter,
            'type' => $type,
            'search' => $search,
        ]);
    }
}

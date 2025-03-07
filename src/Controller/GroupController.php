<?php

namespace App\Controller;

use App\Repository\BookRepository;
use http\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GroupController extends AbstractController
{
    public function __construct(private readonly BookRepository $bookRepository)
    {
    }

    #[Route('/groups/{type}/{letter}', name: 'app_groups')]
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
        $search = $request->get('search', '');

        if (!is_string($search)) {
            throw new RuntimeException('Invalid search type');
        }

        $group = match ($search) {
            default => array_filter($group, static fn (mixed $item) => str_contains(strtolower($item['item']), strtolower($search))),
            '' => array_filter($group, static fn (mixed $item) => str_starts_with(strtolower($item['item']), $letter)),
        };

        return $this->render('group/index.html.twig', [
            'group' => $group,
            'letter' => $letter,
            'type' => $type,
            'search' => $search,
        ]);
    }
}

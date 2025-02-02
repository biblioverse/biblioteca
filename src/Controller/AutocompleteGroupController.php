<?php

namespace App\Controller;

use App\Enum\AgeCategory;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-type GroupType array{ item:string, slug:string, bookCount:int, booksFinished:int, lastBookIndex:int }
 */
class AutocompleteGroupController extends AbstractController
{
    #[Route('/autocomplete/group/{type}', name: 'app_autocomplete_group')]
    public function index(Request $request, BookRepository $bookRepository, string $type, TranslatorInterface $translator): Response
    {
        $query = $request->get('query');
        if (!is_string($query)) {
            return new JsonResponse(['results' => []]);
        }

        $json = ['results' => []];

        if ($type === 'ageCategory') {
            foreach (AgeCategory::cases() as $ageCategory) {
                $json['results'][] = ['value' => $ageCategory->value, 'text' => $translator->trans(AgeCategory::getLabel($ageCategory))];
            }

            return new JsonResponse($json);
        }

        /** @var array<GroupType> $group */
        $group = match ($type) {
            'serie' => $bookRepository->getAllSeries(),
            'authors' => $bookRepository->getAllAuthors(),
            'tags' => $bookRepository->getAllTags(),
            'publisher' => $bookRepository->getAllPublishers(),
            default => [],
        };

        $exactmatch = false;

        if ($type !== 'authors' && $query === '' && $request->get('create', true) !== true) {
            $json['results'][] = ['value' => 'no_'.$type, 'text' => '[No '.$type.' defined]'];
        }
        foreach ($group as $item) {
            if (!str_contains(strtolower($item['item']), strtolower($query))) {
                continue;
            }
            if (strtolower($item['item']) === strtolower($query)) {
                $exactmatch = true;
            }
            $json['results'][] = ['value' => $item['item'], 'text' => $item['item']];
        }

        if (!$exactmatch && strlen($query) > 2 && $request->get('create', true) === true) {
            $json['results'][] = ['value' => $query, 'text' => 'New: '.$query];
        }

        return new JsonResponse($json);
    }
}

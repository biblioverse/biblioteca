<?php

// src/Twig/AppExtension.php

namespace App\Twig;

use App\Service\FilteredBookUrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FilteredBookUrl extends AbstractExtension
{
    public function __construct(private readonly FilteredBookUrlGenerator $filteredBookUrlGenerator, private readonly RouterInterface $router)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('filter_book_url', $this->filteredBookUrl(...)),
            new TwigFunction('current_page_filters', $this->currentPageParams(...)),
        ];
    }

    public function filteredBookUrl(array $params): string
    {
        $params = $this->filteredBookUrlGenerator->getParametersArray($params);
        $defaultParams = $this->filteredBookUrlGenerator::FIELDS_DEFAULT_VALUE;

        $modif = array_udiff_uassoc($params, $defaultParams, static function ($a, $b) {
            if ($a === $b) {
                return 0;
            }
            return 1;
        }, static function ($a, $b) {
            if ($a === $b) {
                return 0;
            }
            return 1;
        });
        unset($modif['submit'], $modif['orderBy']);

        if (count($modif) === 1) {
            $key = array_key_first($modif);
            $route = match ($key) {
                'authors' => 'app_author_detail',
                'serie' => 'app_serie_detail',
                default => 'app_allbooks',
            };
            if ($route !== 'app_allbooks') {
                return $this->router->generate($route, ['name' => $modif[$key]]);
            }
        }

        return $this->router->generate('app_allbooks', $params);
    }

    public function currentPageParams(bool $onlyModified = false): array
    {
        return $this->filteredBookUrlGenerator->getParametersArrayForCurrent($onlyModified);
    }
}

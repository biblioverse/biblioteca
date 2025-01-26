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

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('filter_book_url', $this->filteredBookUrl(...)),
        ];
    }

    public function filteredBookUrl(array $params): string
    {
        $params = $this->filteredBookUrlGenerator->getParametersArray($params);

        $key = '';
        $value = '';
        if (array_key_exists('filterQuery', $params) && preg_match('/^(serie|authors):=`(.+)`\s+$/', (string) $params['filterQuery'], $matches) === 1) {
            $key = $matches[1];
            $value = $matches[2];
        }
        $route = match ($key) {
            'authors' => 'app_author_detail',
            'serie' => 'app_serie_detail',
            default => 'app_allbooks',
        };
        if ($route !== 'app_allbooks') {
            return $this->router->generate($route, ['name' => $value]);
        }

        return $this->router->generate('app_allbooks', $params);
    }
}

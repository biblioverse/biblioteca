<?php

// src/Twig/AppExtension.php

namespace App\Twig;

use App\Service\FilteredBookUrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FilteredBookUrl extends AbstractExtension
{
    public function __construct(private FilteredBookUrlGenerator $filteredBookUrlGenerator, private RouterInterface $router)
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('filter_book_url', [$this, 'filteredBookUrl']),
            new TwigFunction('current_page_filters', [$this, 'currentPageParams']),
        ];
    }

    public function filteredBookUrl(array $params): string
    {
        $params = $this->filteredBookUrlGenerator->getParametersArray($params);

        return $this->router->generate('app_allbooks', $params);
    }

    public function currentPageParams(bool $onlyModified = false): array
    {
        return $this->filteredBookUrlGenerator->getParametersArrayForCurrent($onlyModified);
    }
}

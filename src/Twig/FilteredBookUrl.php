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

        return $this->router->generate('app_allbooks', $params);
    }
}

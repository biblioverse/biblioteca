<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\ThemeExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @codeCoverageIgnore
 */
class ThemeExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('themedTemplate', [ThemeExtensionRuntime::class, 'themedTemplate']),
        ];
    }
}

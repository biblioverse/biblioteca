<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\ThemeExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('themedTemplate', [ThemeExtensionRuntime::class, 'themedTemplate']),
        ];
    }
}

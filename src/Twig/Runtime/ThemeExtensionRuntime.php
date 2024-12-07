<?php

namespace App\Twig\Runtime;

use App\Service\ThemeSelector;
use Twig\Extension\RuntimeExtensionInterface;

class ThemeExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly ThemeSelector $themeSelector)
    {
    }

    public function themedTemplate(string $value): string|array
    {
        $theme = $this->themeSelector->getTheme();

        if ($theme !== null) {
            return ['themes/'.$theme.'/'.$value, $value];
        }

        return $value;
    }
}

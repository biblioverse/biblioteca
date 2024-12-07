<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UniqueIdExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('unique_id', $this->uniqueID(...)),
        ];
    }

    public function uniqueID(string $prefix = '', bool $entropy = false): string
    {
        return uniqid($prefix, $entropy);
    }
}

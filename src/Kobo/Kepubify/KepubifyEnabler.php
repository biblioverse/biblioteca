<?php

namespace App\Kobo\Kepubify;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class KepubifyEnabler
{
    public function __construct(
        #[Autowire(param: 'KEPUBIFY_BIN')]
        private string $kepubifyBinary
    ) {
    }

    public function setKepubifyBinary(string $kepubifyBinary): void
    {
        $this->kepubifyBinary = $kepubifyBinary;
    }

    public function isEnabled(): bool
    {
        return trim($this->kepubifyBinary) !== '';
    }

    public function getKepubifyBinary(): string
    {
        return $this->kepubifyBinary;
    }

    public function disable(): string
    {
        $lastValue = $this->kepubifyBinary;
        $this->kepubifyBinary = '';

        return $lastValue;
    }
}

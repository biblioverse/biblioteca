<?php

namespace App\Kobo\Request;

class ReadingStates
{
    /**
     * @param array<int, ReadingState> $readingStates
     */
    public function __construct(public array $readingStates = [])
    {
    }
}

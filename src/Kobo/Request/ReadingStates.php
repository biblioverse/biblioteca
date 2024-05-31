<?php

namespace App\Kobo\Request;

class ReadingStates
{
    /**
     * @param array<int, ReadingState> $readingState
     */
    public function __construct(array $readingState = [])
    {
        $this->readingStates = $readingState;
    }
    /** @var array<int, ReadingState> */
    public array $readingStates = [];
}

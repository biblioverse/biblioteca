<?php

namespace App\Kobo\Request;

class ReadingStateStatistics
{
    public ?\DateTimeImmutable $lastModified = null;
    public ?int $remainingTimeMinutes = null;
    public ?int $spentReadingMinutes = null;
}

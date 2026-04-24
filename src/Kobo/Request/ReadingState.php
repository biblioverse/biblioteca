<?php

declare(strict_types=1);

namespace App\Kobo\Request;

class ReadingState
{
    public ?Bookmark $currentBookmark = null;
    public ?string $entitlementId = null;
    public ?\DateTimeImmutable $lastModified = null;
    public ?ReadingStateStatistics $statistics = null;
    public ?ReadingStateStatusInfo $statusInfo = null;
}

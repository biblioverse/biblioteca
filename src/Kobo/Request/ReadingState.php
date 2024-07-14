<?php

namespace App\Kobo\Request;

class ReadingState
{
    public ?Bookmark $currentBookmark = null;
    public ?string $entitlementId = null;
    public ?\DateTimeImmutable $lastModified = null;
    public mixed $statistics = null;
    public ?ReadingStateStatusInfo $statusInfo = null;
}

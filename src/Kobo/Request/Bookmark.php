<?php

namespace App\Kobo\Request;

class Bookmark
{
    public ?int $contentSourceProgressPercent = null;
    public ?\DateTime $lastModified = null;
    public mixed $location;

    public ?int $progressPercent = null;
}

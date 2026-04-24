<?php

declare(strict_types=1);

namespace App\Kobo\Request;

class ReadingStateLocation
{
    public ?string $source = null;
    public ?string $type = null; // 'KoboSpan'
    public ?string $value = null; // 'kobo.1.1' represent the book's location div
}

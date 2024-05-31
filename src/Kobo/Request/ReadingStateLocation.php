<?php

namespace App\Kobo\Request;

class ReadingStateLocation
{
    public const TYPE_KOBO_SPAN = 'KoboSpan';
    public const TYPE_KOBO_VALUE = 'kobo.1.1';

    public ?string $source = null;
    public ?string $type = self::TYPE_KOBO_SPAN;
    public ?string $value = self::TYPE_KOBO_VALUE;
}

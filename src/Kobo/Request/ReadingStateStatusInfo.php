<?php

namespace App\Kobo\Request;

class ReadingStateStatusInfo
{
    public const STATUS_READING = 'Reading';
    public const STATUS_FINISHED = 'Finished';

    public ?\DateTime $lastModified = null;

    public ?string $status = null;
}

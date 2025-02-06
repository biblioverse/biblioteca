<?php

namespace App\Enum;

enum ReadStatus: string
{
    case NotStarted = 'rs-not-started';
    case Started = 'rs-started';
    case Finished = 'rs-finished';

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::NotStarted => 'enum.readstatus.not-started',
            self::Started => 'enum.readstatus.started',
            self::Finished => 'enum.readstatus.finished',
        };
    }

    public static function getIcon(self $value): string
    {
        return match ($value) {
            self::NotStarted => 'question-circle',
            self::Started => 'hourglass-split',
            self::Finished => 'check-circle-fill',
        };
    }
}

<?php

namespace App\Enum;

enum ReadingList: string
{
    case ToRead = 'rl-to-read';
    case Ignored = 'rl-ignored';
    case NotDefined = 'rl-undefined';

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            self::ToRead => 'enum.readinglist.toread',
            self::Ignored => 'enum.readinglist.ignored',
            self::NotDefined => 'enum.readinglist.notdefined',
        };
    }
}

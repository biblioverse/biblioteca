<?php

namespace App\Enum;

enum ReadingList: string
{
    case NotDefined = 'rl-undefined';
    case ToRead = 'rl-to-read';
    case Ignored = 'rl-ignored';

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

    public static function getIcon(self $value): string
    {
        return match ($value) {
            self::ToRead => 'bookmark-heart-fill',
            self::Ignored => 'bookmark-x-fill',
            self::NotDefined => 'bookmark',
        };
    }
}

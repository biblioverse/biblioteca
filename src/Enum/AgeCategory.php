<?php

namespace App\Enum;

enum AgeCategory: int
{
    case Everyone = 1;
    case TenPlus = 2;
    case ThirteenPlus = 3;
    case SixteenPlus = 4;
    case Adult = 5;

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(?self $value): string
    {
        if (!$value instanceof AgeCategory) {
            return 'enum.agecategories.notset';
        }

        return match ($value) {
            self::Adult => 'enum.agecategories.adults',
            self::Everyone => 'enum.agecategories.everyone',
            self::SixteenPlus => 'enum.agecategories.sixteenplus',
            self::TenPlus => 'enum.agecategories.tenplus',
            self::ThirteenPlus => 'enum.agecategories.thirteenplus',
        };
    }
}

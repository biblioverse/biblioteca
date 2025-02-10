<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum AgeCategory: int implements TranslatableInterface
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

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        // Translate enum using custom labels
        return match ($this) {
            self::Adult => $translator->trans('enum.agecategories.adults', locale: $locale),
            self::Everyone => $translator->trans('enum.agecategories.everyone', locale: $locale),
            self::SixteenPlus => $translator->trans('enum.agecategories.sixteenplus', locale: $locale),
            self::TenPlus => $translator->trans('enum.agecategories.tenplus', locale: $locale),
            self::ThirteenPlus => $translator->trans('enum.agecategories.thirteenplus', locale: $locale),
        };
    }
}

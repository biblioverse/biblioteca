<?php

namespace App\Enum;

enum AiMessageRole: string
{
    case Assistant = 'assistant';
    case User = 'user';
    case System = 'system';

    public static function getClass(self $value): string
    {
        return match ($value) {
            self::User => 'primary',
            self::System => 'dark',
            self::Assistant => 'light',
        };
    }
}

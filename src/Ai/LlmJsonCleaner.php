<?php

namespace App\Ai;

class LlmJsonCleaner
{
    public static function clean(string $result): string
    {
        $result = trim($result, "´`\n\r\t\v\0 ");
        if (str_starts_with($result, 'json')) {
            $result = substr($result, 4);
        }

        return preg_replace('/<think>.*?<\/think>/s', '', $result) ?? '';
    }
}

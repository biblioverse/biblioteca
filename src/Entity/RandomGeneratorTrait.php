<?php

namespace App\Entity;

trait RandomGeneratorTrait
{
    /**
     * @throws \Exception
     */
    protected function generateRandomString(int $length): string
    {
        if ($length < 1) {
            return '';
        }
        // Generate random bytes
        $bytes = random_bytes(max(1, (int) ($length / 2))); // 32 characters => 16 bytes

        // Convert to a hexadecimal string
        return substr(bin2hex($bytes), 0, $length);
    }
}

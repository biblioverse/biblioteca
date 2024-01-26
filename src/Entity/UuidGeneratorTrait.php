<?php

namespace App\Entity;

trait UuidGeneratorTrait
{
    /**
     * @throws \Exception
     */
    protected function generateUuid(): string
    {
        $data = random_bytes(16);

        // Set the version (4 for randomly generated UUID)
        $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
        // Set bits 6-7 to 10xx (time-based UUID)
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

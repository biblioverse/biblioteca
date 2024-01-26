<?php

namespace App\Kobo\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class KoboNameConverter implements NameConverterInterface
{
    /**
     * JSON EntitlementId => PHP entitlementId
     */
    public function normalize(string $propertyName): string
    {
        return ucfirst($propertyName);
    }

    /**
     * PHP entitlementId => JSON EntitlementId
     */
    public function denormalize(string $propertyName): string
    {
        return lcfirst($propertyName);
    }
}

<?php

namespace App\Kobo\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class KoboNameConverter implements NameConverterInterface
{
    /**
     * JSON EntitlementId => PHP entitlementId
     *
     * @param class-string|null $class
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        return ucfirst($propertyName);
    }

    /**
     * PHP entitlementId => JSON EntitlementId
     *
     * @param class-string|null $class
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        return lcfirst($propertyName);
    }
}

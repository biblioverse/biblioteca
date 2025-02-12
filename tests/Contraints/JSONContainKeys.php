<?php

namespace App\Tests\Contraints;

use PHPUnit\Framework\Constraint\Constraint;

class JSONContainKeys extends Constraint
{
    public function __construct(private readonly array $keys, private readonly ?string $path = null)
    {
    }

    #[\Override]
    public function matches(mixed $other): bool
    {
        try {
            $this->test($other);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * Validate the constraint and throw an exception for the first invalid value encountered.
     * The exception message complete "failing asserting that...".
     * @param array|mixed $other
     */
    public function test($other): void
    {
        if (false === is_iterable($other)) {
            throw new \InvalidArgumentException('JSON is iterable');
        }

        if (!is_array($other)) {
            throw new \InvalidArgumentException('Expected array as JSON');
        }

        $data = $this->extractDataFromPath($other);

        foreach ($this->keys as $key) {
            if (!isset($data[$key])) {
                $keyLabel = $this->path !== null ? sprintf('%s.%s', $this->path, $key) : $key;
                throw new \InvalidArgumentException(sprintf('Key %s exists', $keyLabel));
            }
        }
    }

    #[\Override]
    public function failureDescription(mixed $other): string
    {
        try {
            $this->test($other);

            return 'JSON contains all expected keys';
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }

    #[\Override]
    public function toString(): string
    {
        if ($this->path !== null) {
            return sprintf('JSON at path %s contains all expected keys: %s', $this->path, implode(', ', $this->keys));
        }

        return sprintf('JSON contains all expected keys: %s', implode(', ', $this->keys));
    }

    private function extractDataFromPath(array $other): array
    {
        if ($this->path === null) {
            return $other;
        }

        if (!array_key_exists($this->path, $other)) {
            throw new \InvalidArgumentException(sprintf('Path %s exists', $this->path));
        }

        return $other[$this->path];
    }
}

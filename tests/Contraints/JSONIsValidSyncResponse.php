<?php

namespace App\Tests\Contraints;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsIdentical;

class JSONIsValidSyncResponse extends Constraint
{
    public function __construct(protected array $expectedKeysCount, protected int $pageNum = 1)
    {
        foreach ($this->expectedKeysCount as $key => $count) {
            if (false === in_array($key, self::KNOWN_TYPES, true)) {
                throw new \InvalidArgumentException(sprintf('The type %s is not valid', $key));
            }
            if (false === is_int($count)) {
                throw new \InvalidArgumentException(sprintf('The type %s has an invalid count', $key));
            }
        }
    }

    public const KNOWN_TYPES = [
        'ChangedEntitlement',
        'ChangedReadingState',
        'ChangedTag',
        'DeletedTag',
        'NewEntitlement',
        'NewTag',
        'RemovedPublication',
    ];

    #[\Override]
    public function matches($other): bool
    {
        try {
            $this->test($other);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function toString(): string
    {
        return 'is a valid sync response';
    }

    private function test(mixed $other): void
    {
        if (false === is_array($other)) {
            throw new \InvalidArgumentException('JSON is an array');
        }

        $count = [];
        foreach ($other as $item) {
            $type = $this->getType(array_keys($item));
            match ($type) {
                'NewEntitlement' => $this->assertNewEntitlement($item['NewEntitlement']),
                'ChangedTag' => $this->assertChangedTag(),
                'NewTag' => $this->assertNewTag(),
                'DeletedTag' => $this->assertDeletedTag(),
                'ChangedReadingState' => null,
                'RemovedPublication' => $this->assertRemovedPublication(),
                'ChangedEntitlement' => $this->assertChangedEntitlement($item['ChangedEntitlement']),
                default => throw new \InvalidArgumentException('Unknown type'),
            };
            $count[$type] = ($count[$type] ?? 0) + 1;
        }

        foreach (self::KNOWN_TYPES as $type) {
            (new IsIdentical($this->expectedKeysCount[$type] ?? 0))->evaluate($count[$type] ?? 0, sprintf('The number for %s doesnt matches', $type));
        }

        asort($count);
        asort($this->expectedKeysCount);

        (new IsIdentical($this->expectedKeysCount))->evaluate($count, 'Sync response doesnt contains the right entries count for page '.$this->pageNum, false);
    }

    private function assertChangedTag(): void
    {
    }

    private function assertNewTag(): void
    {
    }

    private function assertDeletedTag(): void
    {
    }

    private function assertRemovedPublication(): void
    {
    }

    private function getType(array $keys): string
    {
        foreach ($keys as $key) {
            if (in_array($key, self::KNOWN_TYPES, true)) {
                return $key;
            }
        }
        throw new \InvalidArgumentException(sprintf(' Unknown type. Expect one of: %s', implode(', ', self::KNOWN_TYPES)));
    }

    private function assertNewEntitlement(mixed $item): void
    {
        $this->assertChangedEntitlement($item);
    }

    private function assertChangedEntitlement(mixed $item): void
    {
        $this->assertNestedKeys([
            'BookEntitlement',
            'BookEntitlement.Accessibility',
            'BookEntitlement.ActivePeriod',
            'BookEntitlement.ActivePeriod.From',
            'BookEntitlement.Created',
            'BookEntitlement.Id',
            'BookEntitlement.Status',
            'BookMetadata.DownloadUrls.0.Format',
            'BookMetadata.DownloadUrls.0.Size',
            'BookMetadata.DownloadUrls.0.Url',
            'BookMetadata.DownloadUrls.0.Platform',
            'ReadingState'], $item);
    }

    private function assertNestedKeys(array $keys, mixed $item): void
    {
        foreach ($keys as $key) {
            (new ArrayHasNestedKey($key))->evaluate($item, sprintf('Entitlement doesnt have a %s key', $key));
        }
    }
}

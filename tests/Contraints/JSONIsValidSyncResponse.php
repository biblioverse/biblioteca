<?php

namespace App\Tests\Contraints;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsIdentical;

/**
 * @phpstan-type ExpectedKeysCount array{NewEntitlement?: int, NewTag?: int, ChangedTag?: int, DeletedTag?: int, ChangedReadingState?: int, RemovedPublication?: int, ChangedEntitlement?: int}
 * @phpstan-type MatchContent array{NewEntitlement: mixed, NewTag: mixed, ChangedTag: mixed, DeletedTag: mixed, ChangedReadingState: mixed, RemovedPublication: mixed, ChangedEntitlement: mixed}
 */
class JSONIsValidSyncResponse extends Constraint
{
    /**
     * @param ExpectedKeysCount $expectedKeysCount
     */
    public function __construct(protected array $expectedKeysCount, protected int $pageNum = 1)
    {
        foreach ($this->expectedKeysCount as $key => $count) {
            if (false === in_array($key, self::KNOWN_TYPES, true)) {
                throw new \InvalidArgumentException(sprintf('The type %s is not valid', $key));
            }
            // @phpstan-ignore-next-line
            if (false === is_int($count)) {
                throw new \InvalidArgumentException(sprintf('The type %s has an invalid count', $key));
            }
        }

        $this->fillEmptyWithZero($this->expectedKeysCount);
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
    public function matches(mixed $other): bool
    {
        try {
            // @phpstan-ignore-next-line
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

    /**
     * @param MatchContent[] $other
     */
    private function test(mixed $other): void
    {
        // @phpstan-ignore-next-line
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

        $this->fillEmptyWithZero($count);

        ksort($count);
        ksort($this->expectedKeysCount);

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

    /**
     * @param array<string, int> $values
     */
    private function fillEmptyWithZero(array &$values): void
    {
        foreach (self::KNOWN_TYPES as $type) {
            if (false === array_key_exists($type, $values)) {
                $values[$type] = 0;
            }
        }
    }
}

<?php

namespace App\Tests\Kobo;

use App\Kobo\SyncToken\SyncTokenV2;
use PHPUnit\Framework\TestCase;

class SyncTokenV2Test extends TestCase
{
    public const array TOKEN_DATA = [
        'SubscriptionEntitlements' => [
            'IsInitial' => null,
            'GenerationTime' => null,
            'Timestamp' => '1900-01-01T00:00:00Z',
            'CheckSum' => null,
            'Id' => '00000000-0000-0000-0000-000000000000',
        ],
        'FutureSubscriptionEntitlements' => null,
        'Entitlements' => [
            'IsInitial' => null,
            'GenerationTime' => null,
            'Timestamp' => '2025-07-10T08:29:17Z',
            'CheckSum' => '{"SkipCreated":false,"CreatedCount":0,"Created":[],"SkipModified":false,"Modified":[]}',
            'Id' => '00000000-0000-0000-0000-000000000000',
        ],
        'DeletedEntitlements' => [
            'IsInitial' => null,
            'GenerationTime' => null,
            'Timestamp' => '2025-09-02T08:29:17Z',
            'CheckSum' => null,
            'Id' => '00000000-0000-0000-0000-000000000000',
        ],
        'ReadingStates' => [
            'IsInitial' => null,
            'GenerationTime' => null,
            'Timestamp' => '2025-09-11T08:29:17Z',
            'CheckSum' => null,
            'Id' => 'b69a579b-c1a7-4227-98af-47fdd2104c72',
        ],
        'Tags' => [
            'IsInitial' => null,
            'GenerationTime' => null,
            'Timestamp' => '2025-08-05T08:29:17Z',
            'CheckSum' => null,
            'Id' => '00000000-0000-0000-0000-000000000000',
        ],
        'DeletedTags' => [
            'IsInitial' => null,
            'GenerationTime' => null,
            'Timestamp' => '2024-02-02T13:35:49Z',
            'CheckSum' => null,
            'Id' => '1ac25199-22e0-49fe-ad31-e45f07080b04',
        ],
        'ProductMetadata' => [
            'IsInitial' => null,
            'GenerationTime' => null,
            'Timestamp' => '2025-02-01T08:29:17Z',
            'CheckSum' => null,
            'Id' => '00000000-0000-0000-0000-000000000000',
        ],
    ];

    public function testCreation(): void
    {
        $token = $this->createToken();
        self::assertEquals(new \DateTimeImmutable('2025-09-02T08:29:17Z'), $token->getArchiveLastModified()); // DeletedEntitlements.Timestamp
        self::assertEquals(new \DateTimeImmutable('2025-07-10T08:29:17Z'), $token->getLastCreated()); // Entitlements.Timestamp
        self::assertEquals(new \DateTimeImmutable('2025-02-01T08:29:17Z'), $token->getLastModified()); // ProductMetadata.Timestamp
        self::assertEquals(new \DateTimeImmutable('2025-09-11T08:29:17Z'), $token->getReadingStateLastModified()); // ReadingStates.Timestamp
        self::assertEquals(new \DateTimeImmutable('2025-08-05T08:29:17Z'), $token->getTagLastModified()); // Tags.Timestamp
        self::assertEquals(new \DateTimeImmutable('2024-02-02T13:35:49Z'), $token->getDeletedTagLastModified()); // DeletedTags.Timestamp
        self::assertTrue($token->isContinuation());
    }

    public function testToArray(): void
    {
        $syncToken = $this->createToken();

        self::assertSame($this->getExpected(), $syncToken->toArray());
    }

    private function createToken(): SyncTokenV2
    {
        return new SyncTokenV2(self::TOKEN_DATA, true);
    }

    private function getExpected(): array
    {
        return [
            'data' => self::TOKEN_DATA,
            'isContinuation' => true,
            'filters' => [],
        ];
    }
}

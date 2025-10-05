<?php

namespace App\Tests\Kobo;

use App\Kobo\SyncToken\SyncTokenV1;
use PHPUnit\Framework\TestCase;

class SyncTokenV1Test extends TestCase
{
    public function testFromArray(): void
    {
        $token = SyncTokenV1::fromArray($this->createToken()->toArray());
        self::assertSame($this->getExpected(), $token->toArray());
    }

    public function testToArray(): void
    {
        $syncToken = $this->createToken();
        self::assertSame($this->getExpected(), $syncToken->toArray());
    }

    private function createToken(): SyncTokenV1
    {
        $syncToken = new SyncTokenV1();
        $syncToken->archiveLastModified = new \DateTimeImmutable('2024-01-01');
        $syncToken->setFilters(['Filter' => 'ALL', 'DownloadUrlFilter' => 'Generic,Android', 'PrioritizeRecentReads' => true]);
        $syncToken->lastCreated = new \DateTimeImmutable('2022-01-01');
        $syncToken->lastModified = new \DateTimeImmutable('2021-01-02');
        $syncToken->rawKoboStoreToken = 'hello world';
        $syncToken->readingStateLastModified = new \DateTimeImmutable('2023-01-01');
        $syncToken->tagLastModified = new \DateTimeImmutable('2026-01-01');
        $syncToken->deletedTagLastModified = new \DateTimeImmutable('2026-01-01');
        $syncToken->version = '1-1-0';

        return $syncToken;
    }

    private function getExpected(): array
    {
        // Sort alphabetically
        return [
            'archiveLastModified' => '2024-01-01T00:00:00+00:00',
            'filters' => [
                'Filter' => 'ALL',
                'DownloadUrlFilter' => 'Generic,Android',
                'PrioritizeRecentReads' => true,
            ],
            'lastCreated' => '2022-01-01T00:00:00+00:00',
            'lastModified' => '2021-01-02T00:00:00+00:00',
            'page' => 1,
            'rawKoboStoreToken' => 'hello world',
            'readingStateLastModified' => '2023-01-01T00:00:00+00:00',
            'tagLastModified' => '2026-01-01T00:00:00+00:00',
            'version' => '1-1-0',
        ];
    }
}

<?php

namespace App\Tests\Kobo;

use App\Kobo\SyncToken;
use PHPUnit\Framework\TestCase;

class SyncTokenTest extends TestCase
{
    public function testFromArray(): void
    {
        $token = SyncToken::fromArray($this->createToken()->toArray());
        self::assertSame($this->getExpected(), $token->toArray());
    }

    public function testToArray(): void
    {
        $syncToken = $this->createToken();
        self::assertSame($this->getExpected(), $syncToken->toArray());
    }

    private function createToken(): SyncToken
    {
        $syncToken = new SyncToken();
        $syncToken->filters = ['Filter' => 'ALL', 'DownloadUrlFilter' => 'Generic,Android', 'PrioritizeRecentReads' => true];
        $syncToken->lastModified = new \DateTimeImmutable('2021-01-02');
        $syncToken->lastCreated = new \DateTimeImmutable('2022-01-01');
        $syncToken->readingStateLastModified = new \DateTimeImmutable('2023-01-01');
        $syncToken->archiveLastModified = new \DateTimeImmutable('2024-01-01');
        $syncToken->tagLastModified = new \DateTimeImmutable('2026-01-01');
        $syncToken->currentDate = new \DateTimeImmutable('2028-01-01');
        $syncToken->version = '1-1-0';
        $syncToken->rawKoboStoreToken = 'hello world';

        return $syncToken;
    }

    private function getExpected(): array
    {
        return [
            'version' => '1-1-0',
            'currentDate' => '2028-01-01T00:00:00+00:00',
            'lastModified' => '2021-01-02T00:00:00+00:00',
            'lastCreated' => '2022-01-01T00:00:00+00:00',
            'archiveLastModified' => '2024-01-01T00:00:00+00:00',
            'readingStateLastModified' => '2023-01-01T00:00:00+00:00',
            'tagLastModified' => '2026-01-01T00:00:00+00:00',
            'rawKoboStoreToken' => 'hello world',
            'filters' => [
                'Filter' => 'ALL',
                'DownloadUrlFilter' => 'Generic,Android',
                'PrioritizeRecentReads' => true,
            ],
        ];
    }
}

<?php

namespace App\Tests\Kobo;

use App\Kobo\SyncToken\SyncTokenInterface;
use App\Kobo\SyncToken\SyncTokenSerializer;
use App\Kobo\SyncToken\SyncTokenV1;
use App\Kobo\SyncToken\SyncTokenV2;
use PHPUnit\Framework\TestCase;

class SyncTokenSerializerTest extends TestCase
{
    public function testToArray(): void
    {
        $mock = $this->createMock(SyncTokenInterface::class);
        $mock->expects($this->once())
            ->method('toArray')
            ->willReturn(['foo' => 'bar']);

        self::assertSame(['foo' => 'bar'], SyncTokenSerializer::toArray($mock));
    }

    public function testFromArrayReturnsV2(): void
    {
        $data = ['isContinuation' => true, 'data' => [], 'filters' => []];
        self::createStub(SyncTokenV2::class);
        // just check class chosen is V2
        self::assertInstanceOf(SyncTokenV2::class, SyncTokenSerializer::fromArray($data));
    }

    public function testFromArrayReturnsV1(): void
    {
        $data = ['someKey' => 'value'];
        self::assertInstanceOf(SyncTokenV1::class, SyncTokenSerializer::fromArray($data));
    }
}

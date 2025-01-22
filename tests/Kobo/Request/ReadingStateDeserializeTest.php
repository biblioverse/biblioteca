<?php

namespace App\Tests\Kobo\Request;

use App\Kobo\Request\Bookmark;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatusInfo;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReadingStateDeserializeTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        $json = file_get_contents(__DIR__.'/ReadingStateDeserializeTest.json');
        if (false === $json) {
            self::fail('Could not read test file');
        }
        if (false === json_decode($json)) {
            self::fail('Could not decode test file');
        }

        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get('serializer');

        /** @var ReadingStates|mixed $result */
        $result = $serializer->deserialize($json, ReadingStates::class, 'json');
        self::assertInstanceOf(ReadingStates::class, $result, 'Result is not a ReadingStates');
        self::assertIsArray($result->readingStates, 'readingStates is not an array');
        self::assertNotEmpty($result->readingStates, 'readingStates is empty');
        self::assertInstanceOf(Bookmark::class, $result->readingStates[0]->currentBookmark, 'currentBookmark is not a Bookmark');
        self::assertSame(34, $result->readingStates[0]->currentBookmark->progressPercent, 'Progress percent is not correct');
        self::assertInstanceOf(ReadingStateStatusInfo::class, $result->readingStates[0]->statusInfo, 'statusInfo is not a ReadingStateStatusInfo');
        self::assertSame(ReadingStateStatusInfo::STATUS_READING, $result->readingStates[0]->statusInfo->status, 'statusInfo status is not correct');
    }
}

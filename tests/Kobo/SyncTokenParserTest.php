<?php

namespace App\Tests\Kobo;

use App\Kobo\SyncToken\SyncTokenParser;
use App\Kobo\SyncToken\SyncTokenV2;
use PHPUnit\Framework\TestCase;

class SyncTokenParserTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    public function test20250610(): void
    {
        $parser = new SyncTokenParser();

        $header = '{"typ":1,"ver":null,"ptyp":"SyncToken"}';
        $internalData = [
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

        $tokenRaw = base64_encode($header).'.'.base64_encode(json_encode([
            'InternalSyncToken' => base64_encode('{"typ":1,"ver":"v2","ptyp":"SyncToken"}').'.'.base64_encode(json_encode($internalData, JSON_THROW_ON_ERROR)),
            'IsContinuationToken' => true,
        ], JSON_THROW_ON_ERROR));

        $token = $parser->decode($tokenRaw);
        self::assertInstanceOf(SyncTokenV2::class, $token);

        self::assertEquals($tokenRaw, $parser->encode($token));
    }

    public function testParseToken2(): void
    {
        $parser = new SyncTokenParser();
        $tokenB64 = 'eyJ0eXAiOjEsInZlciI6bnVsbCwicHR5cCI6IlN5bmNUb2tlbiJ9.eyJJbnRlcm5hbFN5bmNUb2tlbiI6ImV5SjBlWEFpT2pFc0luWmxjaUk2SW5ZeUlpd2ljSFI1Y0NJNklsTjVibU5VYjJ0bGJpSjkuZXlKVGRXSnpZM0pwY0hScGIyNUZiblJwZEd4bGJXVnVkSE1pT25zaVNYTkpibWwwYVdGc0lqcHVkV3hzTENKSFpXNWxjbUYwYVc5dVZHbHRaU0k2Ym5Wc2JDd2lWR2x0WlhOMFlXMXdJam9pTVRrd01DMHdNUzB3TVZRd01Eb3dNRG93TUZvaUxDSkRhR1ZqYTFOMWJTSTZiblZzYkN3aVNXUWlPaUl3TURBd01EQXdNQzB3TURBd0xUQXdNREF0TURBd01DMHdNREF3TURBd01EQXdNREFpZlN3aVJuVjBkWEpsVTNWaWMyTnlhWEIwYVc5dVJXNTBhWFJzWlcxbGJuUnpJanB1ZFd4c0xDSkZiblJwZEd4bGJXVnVkSE1pT25zaVNYTkpibWwwYVdGc0lqcHVkV3hzTENKSFpXNWxjbUYwYVc5dVZHbHRaU0k2Ym5Wc2JDd2lWR2x0WlhOMFlXMXdJam9pTWpBeU5TMHdPUzB3TlZReE1Eb3lNem8wTlZvaUxDSkRhR1ZqYTFOMWJTSTZJbnRjSWxOcmFYQkRjbVZoZEdWa1hDSTZabUZzYzJVc1hDSkRjbVZoZEdWa1EyOTFiblJjSWpvd0xGd2lRM0psWVhSbFpGd2lPbHRkTEZ3aVUydHBjRTF2WkdsbWFXVmtYQ0k2Wm1Gc2MyVXNYQ0pOYjJScFptbGxaRndpT2x0ZGZTSXNJa2xrSWpvaU1EQXdNREF3TURBdE1EQXdNQzB3TURBd0xUQXdNREF0TURBd01EQXdNREF3TURBd0luMHNJa1JsYkdWMFpXUkZiblJwZEd4bGJXVnVkSE1pT25zaVNYTkpibWwwYVdGc0lqcHVkV3hzTENKSFpXNWxjbUYwYVc5dVZHbHRaU0k2Ym5Wc2JDd2lWR2x0WlhOMFlXMXdJam9pTWpBeU5TMHdPUzB3TlZReE1Eb3lNem8wTlZvaUxDSkRhR1ZqYTFOMWJTSTZiblZzYkN3aVNXUWlPaUl3TURBd01EQXdNQzB3TURBd0xUQXdNREF0TURBd01DMHdNREF3TURBd01EQXdNREFpZlN3aVVtVmhaR2x1WjFOMFlYUmxjeUk2ZXlKSmMwbHVhWFJwWVd3aU9tNTFiR3dzSWtkbGJtVnlZWFJwYjI1VWFXMWxJanB1ZFd4c0xDSlVhVzFsYzNSaGJYQWlPaUl5TURJMUxUQTVMVEExVkRFd09qSXpPalEzV2lJc0lrTm9aV05yVTNWdElqcHVkV3hzTENKSlpDSTZJbVpoTXpjMVlqSXpMV016WWpZdE5EZzBaUzA0TW1FeExUTTRNV1pqTlRrMk5HTXhNeUo5TENKVVlXZHpJanA3SWtselNXNXBkR2xoYkNJNmJuVnNiQ3dpUjJWdVpYSmhkR2x2YmxScGJXVWlPbTUxYkd3c0lsUnBiV1Z6ZEdGdGNDSTZJakl3TWpVdE1Ea3RNRFZVTVRBNk1qTTZORFZhSWl3aVEyaGxZMnRUZFcwaU9tNTFiR3dzSWtsa0lqb2lNREF3TURBd01EQXRNREF3TUMwd01EQXdMVEF3TURBdE1EQXdNREF3TURBd01EQXdJbjBzSWtSbGJHVjBaV1JVWVdkeklqcDdJa2x6U1c1cGRHbGhiQ0k2Ym5Wc2JDd2lSMlZ1WlhKaGRHbHZibFJwYldVaU9tNTFiR3dzSWxScGJXVnpkR0Z0Y0NJNklqSXdNalF0TURJdE1ESlVNVE02TXpVNk5EbGFJaXdpUTJobFkydFRkVzBpT201MWJHd3NJa2xrSWpvaU1XRmpNalV4T1RrdE1qSmxNQzAwT1dabExXRmtNekV0WlRRMVpqQTNNRGd3WWpBMEluMHNJbEJ5YjJSMVkzUk5aWFJoWkdGMFlTSTZleUpKYzBsdWFYUnBZV3dpT201MWJHd3NJa2RsYm1WeVlYUnBiMjVVYVcxbElqcHVkV3hzTENKVWFXMWxjM1JoYlhBaU9pSXlNREkxTFRBNUxUQTFWREV3T2pJek9qUTFXaUlzSWtOb1pXTnJVM1Z0SWpwdWRXeHNMQ0pKWkNJNklqQXdNREF3TURBd0xUQXdNREF0TURBd01DMHdNREF3TFRBd01EQXdNREF3TURBd01DSjlmUSIsIklzQ29udGludWF0aW9uVG9rZW4iOmZhbHNlfQ';
        $token = $parser->decode($tokenB64);
        self::assertInstanceOf(SyncTokenV2::class, $token);
    }
}

<?php

namespace App\Tests\Controller\Kobo;


class KoboAnalyticsControllerTest extends AbstractKoboControllerTest
{
    const SERIAL_NUMBER = 'N9413679432456';
    const APP_VERSION = '4.38.21908';

    public function testPostEvent(): void
    {
        $body = [
            'AffiliateName' => 'Kobo',
            'ApplicationVersion' => self::APP_VERSION,
            'Events' => [
                [
                    'Attributes' => [
                        'next-in-series' => 'false',
                        'ratings' => 'true',
                        'related' => 'false',
                        'stats' => 'false'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'EndOfBookView',
                    'Id' => '254661d2-3960-4669-8e16-2d062844e3e7',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:24:49Z'
                ],
                [
                    'Attributes' => [
                        'Origin' => 'MarkAsFinished',
                        'Screen' => '',
                        'volumeid' => 'a22c0264-2983-4cbb-a688-27a15ea89fa7'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'MarkAsFinished',
                    'Id' => '85d678a4-6675-4cfc-be8a-fab17b0df03e',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:24:50Z'],
                [
                    'Attributes' => [
                        'ContentType' => 'book',
                        'Origin' => 'AddToShelf',
                        'Screen' => '',
                        'volumeid' => 'ddb50dfb-d801-4ff6-9800-b7ac2b5c8fb1'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'AddToCollection',
                    'Id' => '4fd7be9b-21ef-427a-88d1-6b1e7377a2e6',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:24:59Z',
                ],
                [
                    'Attributes' => [
                        'ViewType' => 'MyCollections'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'AccessLibrary',
                    'Id' => '8719d2bb-93ac-444b-8b93-3484cce015ea',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:36:11Z',],
                [
                    'Attributes' => [
                        'tab' => 'Collections',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'LibraryTabSelected',
                    'Id' => 'dafb165f-e9c8-40ad-bd7d-2057bcb63bb8',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:36:11Z',
                ],
                [
                    'Attributes' => [
                        'Action' => 'Sync'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'StatusBarOption',
                    'Id' => 'a18cf3e9-2e8b-40a9-ba14-5f2bcbe9bd12',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:45:54Z',
                ],
                [
                    'Attributes' => [
                        'Origin' => 'SyncMenu',
                        'Screen' => 'My Books',
                        'SecondsSinceLastSync' => '1271',
                        'isFullSync' => 'No'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'ManualSync',
                    'Id' => 'd969c797-c1ab-4ce5-b68b-908a3d85ca1b',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:45:55Z'
                ],
                [
                    'Attributes' => [
                        'AccountType' => 'Adult',
                        'Affiliate' => 'Kobo',
                        'CustomerType' => 'RMR',
                        'DeviceModel' => 'Kobo Libra H2O',
                        'ExternalSDTotal' => '0',
                        'ExternalSDUsed' => '0',
                        'HasSubscription' => 'false',
                        'InternalTotal' => '6892',
                        'InternalUsed' => '54',
                        'KoboSuperPointsBalance' => '0',
                        'KoboSuperPointsStatus' => '',
                        'LastReadingFont' => 'default',
                        'OSVersion' => '4.1.15',
                        'SDCardStatus' => 'No',
                        'StorageSize' => '6892'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'UserMetadataUpdate',
                    'Id' => 'f4459943-8071-41ad-ba6d-877939b1d916',
                    'Metrics' => [
                        'DownloadedLibrarySize' => 3,
                        'LibrarySize' => 7,
                        'NumberOfBorrowedBooks' => 0,
                        'NumberOfDownloadedBorrowedBooks' => 0,
                        'NumberOfDownloadedFreeBooks' => 0,
                        'NumberOfDownloadedPaidBooks' => 3,
                        'NumberOfDownloadedPreviews' => 0,
                        'NumberOfDownloadedSubscribedBooks' => 0,
                        'NumberOfFixedLayoutBooks' => 0,
                        'NumberOfFreeBooks' => 0,
                        'NumberOfItemsOnCurrentReads' => 2,
                        'NumberOfPaidBooks' => 7,
                        'NumberOfPreviews' => 0,
                        'NumberOfReflowableBooks' => 7,
                        'NumberOfShelves' => 7,
                        'NumberOfSideloadedBooks' => 0,
                        'NumberOfSubscribedBooks' => 0,
                    ],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:45:59Z',
                ],
                [
                    'Attributes' => [
                        'Origin' => 'SyncMenu',
                        'Screen' => 'My Books',
                        'SecondsSinceLastSync' => '159',
                        'isFullSync' => 'No',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'ManualSync',
                    'Id' => 'ecd3df53-dbfd-4abc-8ee8-3e483e1da5e4',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:48:54Z',
                ],
                [
                    'Attributes' => [
                        'AccountType' => 'Adult',
                        'Affiliate' => 'Kobo',
                        'CustomerType' => 'RMR',
                        'DeviceModel' => 'Kobo Libra H2O',
                        'ExternalSDTotal' => '0',
                        'ExternalSDUsed' => '0',
                        'HasSubscription' => 'false',
                        'InternalTotal' => '6892',
                        'InternalUsed' => '54',
                        'KoboSuperPointsBalance' => '0',
                        'KoboSuperPointsStatus' => '',
                        'LastReadingFont' => 'default',
                        'OSVersion' => '4.1.15',
                        'SDCardStatus' => 'No',
                        'StorageSize' => '6892',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'UserMetadataUpdate',
                    'Id' => 'ad2e1f6a-1828-45d6-aaa2-99a20840a917',
                    'Metrics' => [
                        'DownloadedLibrarySize' => 3,
                        'LibrarySize' => 7,
                        'NumberOfBorrowedBooks' => 0,
                        'NumberOfDownloadedBorrowedBooks' => 0,
                        'NumberOfDownloadedFreeBooks' => 0,
                        'NumberOfDownloadedPaidBooks' => 3,
                        'NumberOfDownloadedPreviews' => 0,
                        'NumberOfDownloadedSubscribedBooks' => 0,
                        'NumberOfFixedLayoutBooks' => 0,
                        'NumberOfFreeBooks' => 0,
                        'NumberOfItemsOnCurrentReads' => 2,
                        'NumberOfPaidBooks' => 7,
                        'NumberOfPreviews' => 0,
                        'NumberOfReflowableBooks' => 7,
                        'NumberOfShelves' => 7,
                        'NumberOfSideloadedBooks' => 0,
                        'NumberOfSubscribedBooks' => 0
                    ],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:48:59Z'
                ],
                [
                    'Attributes' => [
                        'origin' => 'MyCollections',
                        'viewType' => 'Home',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'Home',
                    'Id' => 'f2b29a28-9fd3-4545-9996-6a33b3cc1604',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:49:23Z',
                ],
                [
                    'Attributes' => [
                        'action' => 'MyBooks'
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'HomeWidgetClicked',
                    'Id' => '6ba6970e-7d51-4819-b24e-f8b465231835',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:49:27Z',
                ],
                [
                    'Attributes' => ['ViewType' => 'MyCollections',],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'AccessLibrary',
                    'Id' => 'ec12e3c1-fcb8-493a-84a5-e493b06c6572',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:49:28Z'
                ],
                [
                    'Attributes' => ['tab' => 'Books',],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'LibraryTabSelected',
                    'Id' => '6f25379e-765c-4330-a318-91aaac1c9ac0',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:49:31Z'
                ],
                [
                    'Attributes' =>
                        ['BookSize' => '3.15202331542969',
                            'ContentFormat' => 'application/x-kobo-epub+zip',
                            'Monetization' => 'Paid'
                        ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'download_content',
                    'Id' => '9dce2402-6d77-403d-bf68-83fa98336f89',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:49:37Z',],
                [
                    'Attributes' => [
                        'AccountType' => 'Adult',
                        'Affiliate' => 'Kobo',
                        'CustomerType' => 'RMR',
                        'DeviceModel' => 'Kobo Libra H2O',
                        'ExternalSDTotal' => '0', 'ExternalSDUsed' => '0',
                        'HasSubscription' => 'false',
                        'InternalTotal' => '6892',
                        'InternalUsed' => '54',
                        'KoboSuperPointsBalance' => '0',
                        'KoboSuperPointsStatus' => '',
                        'LastReadingFont' => 'default',
                        'OSVersion' => '4.1.15',
                        'SDCardStatus' => 'No',
                        'StorageSize' => '6892',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'UserMetadataUpdate',
                    'Id' => '24abd697-7bef-4ece-a647-49a5e0c6af87',
                    'Metrics' => [
                        'DownloadedLibrarySize' => 3,
                        'LibrarySize' => 7,
                        'NumberOfBorrowedBooks' => 0,
                        'NumberOfDownloadedBorrowedBooks' => 0,
                        'NumberOfDownloadedFreeBooks' => 0,
                        'NumberOfDownloadedPaidBooks' => 3,
                        'NumberOfDownloadedPreviews' => 0,
                        'NumberOfDownloadedSubscribedBooks' => 0,
                        'NumberOfFixedLayoutBooks' => 0,
                        'NumberOfFreeBooks' => 0,
                        'NumberOfItemsOnCurrentReads' => 2,
                        'NumberOfPaidBooks' => 7,
                        'NumberOfPreviews' => 0,
                        'NumberOfReflowableBooks' => 7,
                        'NumberOfShelves' => 7,
                        'NumberOfSideloadedBooks' => 0,
                        'NumberOfSubscribedBooks' => 0,
                    ],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:49:39Z',],
                [
                    'Attributes' => [
                        'BookSize' => '3.15202331542969',
                        'ContentFormat' => 'application/x-kobo-epub+zip',
                        'Monetization' => 'Paid',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'download_content',
                    'Id' => '68dc6e57-f7b4-4527-8552-c2df98f46b40',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:50:03Z',
                ],
                [
                    'Attributes' => [
                        'BookSize' => '6.14080905914307',
                        'ContentFormat' => 'application/x-kobo-epub+zip',
                        'Monetization' => 'Paid',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'download_content',
                    'Id' => '60194206-7000-400a-af56-a0feb4eb92b4',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:50:11Z',
                ],
                [
                    'Attributes' => [
                        'AccountType' => 'Adult',
                        'Affiliate' => 'Kobo',
                        'CustomerType' => 'RMR',
                        'DeviceModel' => 'Kobo Libra H2O',
                        'ExternalSDTotal' => '0',
                        'ExternalSDUsed' => '0',
                        'HasSubscription' => 'false',
                        'InternalTotal' => '6892',
                        'InternalUsed' => '54',
                        'KoboSuperPointsBalance' => '0',
                        'KoboSuperPointsStatus' => '',
                        'LastReadingFont' => 'default',
                        'OSVersion' => '4.1.15',
                        'SDCardStatus' => 'No',
                        'StorageSize' => '6892',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'UserMetadataUpdate',
                    'Id' => 'ac041e58-61d2-45e6-ac6c-2da5a82f1d86',
                    'Metrics' => [
                        'DownloadedLibrarySize' => 3,
                        'LibrarySize' => 7,
                        'NumberOfBorrowedBooks' => 0,
                        'NumberOfDownloadedBorrowedBooks' => 0,
                        'NumberOfDownloadedFreeBooks' => 0,
                        'NumberOfDownloadedPaidBooks' => 3,
                        'NumberOfDownloadedPreviews' => 0,
                        'NumberOfDownloadedSubscribedBooks' => 0,
                        'NumberOfFixedLayoutBooks' => 0,
                        'NumberOfFreeBooks' => 0,
                        'NumberOfItemsOnCurrentReads' => 2,
                        'NumberOfPaidBooks' => 7,
                        'NumberOfPreviews' => 0,
                        'NumberOfReflowableBooks' => 7,
                        'NumberOfShelves' => 7,
                        'NumberOfSideloadedBooks' => 0,
                        'NumberOfSubscribedBooks' => 0,
                    ],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T09:50:15Z',
                ],
                [
                    'Attributes' => [
                        'BookSize' => '3.15202331542969',
                        'ContentFormat' => 'application/x-kobo-epub+zip',
                        'Monetization' => 'Paid',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'download_content',
                    'Id' => '1baa9180-b281-49b7-961a-95157ed01d55',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T10:01:16Z',
                ],
                [
                    'Attributes' => [
                        'reason' => 'WebRequestErr',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'FailedSync',
                    'Id' => '905ea73b-206a-4e11-b57a-fd756d97c9c1',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T10:01:18Z',
                ],
                [
                    'Attributes' => [
                        'BookSize' => '3.15202331542969',
                        'ContentFormat' => 'application/x-kobo-epub+zip',
                        'Monetization' => 'Paid',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'download_content',
                    'Id' => '7e5bb51d-20d6-4bda-b39f-ccc6312e9e16',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T10:02:47Z',
                ],
                [
                    'Attributes' => [
                        'Action' => 'Sync',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'StatusBarOption',
                    'Id' => '419a4a9a-4841-4ec6-bcde-8738ed9b5f6b',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T10:02:51Z',
                ],
                [
                    'Attributes' => [
                        'reason' => 'WebRequestErr',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'FailedSync',
                    'Id' => '59b85f52-56e3-42c2-a5aa-54771e354a4d',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T10:02:52Z',
                ],
                [
                    'Attributes' => [
                        'Origin' => 'SyncMenu',
                        'Screen' => 'My Books',
                        'SecondsSinceLastSync' => '739',
                        'isFullSync' => 'No',
                    ],
                    'ClientApplicationVersion' => self::APP_VERSION,
                    'EventType' => 'ManualSync',
                    'Id' => 'd9d45290-f597-4956-a0c7-7af3143a31da',
                    'Metrics' => [],
                    'TestGroups' => [],
                    'Timestamp' => '2024-01-28T10:02:53Z',
                ],
            ],
            'PlatformId' => '00000000-0000-0000-0000-000000000384',
            'SerialNumber' => self::SERIAL_NUMBER,
        ];
        $client = static::getClient();
        $client?->setServerParameter('HTTP_CONNECTION', 'keep-alive');
        $client?->request('POST', '/kobo/'.$this->accessKey.'/v1/analytics/event', [
            'json' => $body,
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Connection', 'keep-alive');
    }
}
<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StateResponse extends JsonResponse
{
    public function __construct(Book|string $bookOrUuid, bool $isSuccess = true)
    {
        parent::__construct([
            'RequestResult' => $isSuccess ? 'Success' : 'FailedCommands',
            'UpdateResults' => [
                [
                    'CurrentBookmarkResult' => [
                        'Result' => 'Success',
                    ],
                    'EntitlementId' => $bookOrUuid instanceof Book ? $bookOrUuid->getUuid() : $bookOrUuid,
                    'StatisticsResult' => [
                        'Result' => 'Success',
                    ],
                    'StatusInfoResult' => [
                        'Result' => $isSuccess ? 'Success' : 'Conflict',
                    ],
                ],
            ],
        ], Response::HTTP_OK);
    }
}

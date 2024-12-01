<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StateResponse extends JsonResponse
{
    public function __construct(Book $book, bool $isSuccess = true)
    {
        parent::__construct([
            'RequestResult' => $isSuccess ? 'Success' : 'FailedCommands',
            'UpdateResults' => [
                [
                    'CurrentBookmarkResult' => [
                        'Result' => 'Success',
                    ],
                    'EntitlementId' => $book->getUuid(),
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

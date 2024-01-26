<?php

namespace App\Kobo\Response;

use App\Entity\Book;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StateResponse extends JsonResponse
{
    public function __construct(Book $book)
    {
        parent::__construct([
            'RequestResult' => 'Success',
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
                        'Result' => 'Success',
                    ],
                ],
            ],
        ], Response::HTTP_NO_CONTENT);
    }
}

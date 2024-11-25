<?php

namespace App\Controller\Kobo;

use App\Entity\Book;
use App\Entity\BookmarkUser;
use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Request\Bookmark;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatusInfo;
use App\Kobo\Response\ReadingStateResponseFactory;
use App\Kobo\Response\StateResponse;
use App\Kobo\SyncToken;
use App\Repository\BookRepository;
use App\Service\BookProgressionService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use MongoDB\BSON\Javascript;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v3', name: 'kobo_apiv3')]
class KoboAnnotationsController extends AbstractController
{
    /**
     * Update reading state.
     **/
    #[Route('/content/{uuid}/annotations', name: 'api_v3_annotations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'])]
    public function state(string $uuid, Request $request): Response|JsonResponse
    {
        return new JsonResponse([]);
    }
}
